<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use He4rt\Activity\Message\Enums\MessageSourceKind;
use He4rt\Activity\Message\Models\MembershipEvent;
use He4rt\Activity\Message\Models\Message;
use He4rt\Activity\Reaction\Models\Reaction;
use He4rt\Activity\Retrospective\DiscordSource;
use He4rt\Activity\Voice\Models\Voice;
use He4rt\Community\Retrospective\DTOs\Period;
use He4rt\Community\Retrospective\DTOs\SourceFilters;
use He4rt\Community\Retrospective\DTOs\SourceResult;
use He4rt\Community\Retrospective\Enums\ExclusionKind;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;

beforeEach(function (): void {
    $this->since = CarbonImmutable::parse('2026-06-01 00:00:00');
    $this->until = CarbonImmutable::parse('2026-06-07 23:59:59');
    $this->collect = fn (bool $hideBots = true): SourceResult => new DiscordSource()->collect(
        Period::of($this->since, $this->until),
        new SourceFilters(hideBots: $hideBots),
    );
});

/**
 * @return array<string, mixed>
 */
function dcSlide(SourceResult $result, string $kind): array
{
    foreach ($result->slides as $slide) {
        if ($slide->kind() === $kind) {
            return $slide->toArray();
        }
    }

    return [];
}

function dcIdentity(string $name): ExternalIdentity
{
    return ExternalIdentity::factory()->create([
        'provider' => IdentityProvider::Discord,
        'metadata' => ['username' => $name],
    ]);
}

/**
 * @param  array<string, mixed>  $attributes
 */
function dcReaction(string $messageId, string $emojiKey, int $count, ?string $emojiId = null): void
{
    $reaction = new Reaction();
    $reaction->reactable_type = 'message';
    $reaction->reactable_id = $messageId;
    $reaction->emoji_key = $emojiKey;
    $reaction->emoji_name = $emojiKey;
    $reaction->emoji_id = $emojiId;
    $reaction->count = $count;
    $reaction->count_burst = 0;
    $reaction->count_normal = $count;
    $reaction->save();
}

function dcMembership(string $identityId, string $kind, string $occurredAt): void
{
    $event = new MembershipEvent();
    $event->external_identity_id = $identityId;
    $event->kind = $kind;
    $event->occurred_at = CarbonImmutable::parse($occurredAt);
    $event->save();
}

it('identifica-se como a fonte discord', function (): void {
    expect(new DiscordSource()->key())->toBe('discord');
});

it('devolve resultado vazio sem dado no recorte', function (): void {
    expect(($this->collect)()->isEmpty())->toBeTrue();
});

it('conta mensagens do recorte, escondendo bots e mantendo linhas sem source_kind', function (): void {
    $alice = dcIdentity('Alice');
    $bob = dcIdentity('Bob');

    Message::factory()->create(['external_identity_id' => $alice->id, 'sent_at' => '2026-06-02']);
    Message::factory()->create(['external_identity_id' => $alice->id, 'sent_at' => '2026-06-03']);
    Message::factory()->create(['external_identity_id' => $bob->id, 'sent_at' => '2026-06-02']);
    Message::factory()->create(['external_identity_id' => $bob->id, 'sent_at' => '2026-06-02', 'source_kind' => MessageSourceKind::Bot]);
    Message::factory()->create(['external_identity_id' => $alice->id, 'sent_at' => '2026-05-15']);

    $messages = dcSlide(($this->collect)(), 'discord.messages');

    expect($messages['total'])->toBe(3)
        ->and($messages['chatters'][0])->toMatchArray(['name' => 'Alice', 'messages' => 2])
        ->and($messages['chatters'][1])->toMatchArray(['name' => 'Bob', 'messages' => 1]);
});

it('mantém bots quando hideBots é falso', function (): void {
    $bot = dcIdentity('Robô');
    Message::factory()->create(['external_identity_id' => $bot->id, 'sent_at' => '2026-06-02', 'source_kind' => MessageSourceKind::Bot]);

    expect(dcSlide(($this->collect)(false), 'discord.messages')['total'])->toBe(1)
        ->and(($this->collect)()->isEmpty())->toBeTrue();
});

it('escopa mensagens por sent_at, não por created_at', function (): void {
    $alice = dcIdentity('Alice');
    // sent_at fora do recorte, created_at = agora (dentro): deve ficar de fora.
    Message::factory()->create(['external_identity_id' => $alice->id, 'sent_at' => '2026-05-01']);

    expect(($this->collect)()->isEmpty())->toBeTrue();
});

it('destaca mensagens com reação e fixadas, e a mais reagida', function (): void {
    $alice = dcIdentity('Alice');

    $top = Message::factory()->create(['external_identity_id' => $alice->id, 'sent_at' => '2026-06-02', 'reactions_total' => 12, 'content' => 'mensagem campeã']);
    Message::factory()->create(['external_identity_id' => $alice->id, 'sent_at' => '2026-06-02', 'reactions_total' => 0]);
    Message::factory()->create(['external_identity_id' => $alice->id, 'sent_at' => '2026-06-03', 'is_pinned' => true]);

    $result = ($this->collect)();
    $messages = dcSlide($result, 'discord.messages');
    $topMessage = dcSlide($result, 'discord.top_message');

    expect($messages['with_reactions'])->toBe(1)
        ->and($messages['pinned'])->toBe(1)
        ->and($topMessage['messages'][0])->toMatchArray(['author' => 'Alice', 'reactions' => 12])
        ->and($topMessage['messages'][0]['content'])->toBe('mensagem campeã');
});

it('conta as pessoas do papo e aponta o dia mais falante', function (): void {
    $alice = dcIdentity('Alice');
    $bob = dcIdentity('Bob');

    Message::factory()->create(['external_identity_id' => $alice->id, 'sent_at' => '2026-06-02 15:00:00']);
    Message::factory()->count(3)->create(['external_identity_id' => $bob->id, 'sent_at' => '2026-06-04 15:00:00']);

    $messages = dcSlide(($this->collect)(), 'discord.messages');

    expect($messages['people'])->toBe(2)
        ->and($messages['peak'])->toMatchArray(['date' => '04/06', 'messages' => 3]);
});

it('devolve o ritmo da semana com as 7 posições, no fuso de exibição', function (): void {
    $alice = dcIdentity('Alice');

    // 15h UTC = 12h em São Paulo: terça segue terça (ISO 2).
    Message::factory()->count(2)->create(['external_identity_id' => $alice->id, 'sent_at' => '2026-06-02 15:00:00']);
    // 1h UTC da terça = 22h de segunda em São Paulo (ISO 1).
    Message::factory()->create(['external_identity_id' => $alice->id, 'sent_at' => '2026-06-02 01:00:00']);

    $messages = dcSlide(($this->collect)(), 'discord.messages');

    expect($messages['days'])->toHaveCount(7)
        ->and(array_column($messages['days'], 'weekday'))->toBe(range(1, 7))
        ->and($messages['days'][0])->toMatchArray(['weekday' => 1, 'messages' => 1])
        ->and($messages['days'][1])->toMatchArray(['weekday' => 2, 'messages' => 2]);
});

it('devolve as 24 horas do histograma de mensagens, inclusive as vazias', function (): void {
    $alice = dcIdentity('Alice');

    Message::factory()->create(['external_identity_id' => $alice->id, 'sent_at' => '2026-06-02 15:00:00']);

    $messages = dcSlide(($this->collect)(), 'discord.messages');

    expect($messages['hours'])->toHaveCount(24)
        ->and(array_column($messages['hours'], 'hour'))->toBe(range(0, 23))
        ->and(array_sum(array_column($messages['hours'], 'messages')))->toBe(1);
});

it('rankeia chatters com o XP e as reações que as mensagens puxaram', function (): void {
    $alice = dcIdentity('Alice');

    Message::factory()->create(['external_identity_id' => $alice->id, 'sent_at' => '2026-06-02', 'obtained_experience' => 10, 'reactions_total' => 3]);
    Message::factory()->create(['external_identity_id' => $alice->id, 'sent_at' => '2026-06-03', 'obtained_experience' => 20, 'reactions_total' => 0]);

    $messages = dcSlide(($this->collect)(), 'discord.messages');

    expect($messages['chatters'][0])->toMatchArray(['name' => 'Alice', 'messages' => 2, 'xp' => 30, 'reactions' => 3]);
});

it('agrega o board de voz por participantes, XP e canais', function (): void {
    $alice = dcIdentity('Alice');
    $bob = dcIdentity('Bob');

    Voice::factory()->create(['external_identity_id' => $alice->id, 'channel_name' => 'geral', 'obtained_experience' => 10, 'occurred_at' => '2026-06-02']);
    Voice::factory()->create(['external_identity_id' => $alice->id, 'channel_name' => 'geral', 'obtained_experience' => 20, 'occurred_at' => '2026-06-03']);
    Voice::factory()->create(['external_identity_id' => $bob->id, 'channel_name' => 'estudos', 'obtained_experience' => 5, 'occurred_at' => '2026-06-02']);
    Voice::factory()->create(['external_identity_id' => $bob->id, 'channel_name' => 'geral', 'obtained_experience' => 99, 'occurred_at' => '2026-05-01']);

    $voice = dcSlide(($this->collect)(), 'discord.voice_board');

    expect($voice['participants'])->toBe(2)
        ->and($voice['xp'])->toBe(35)
        ->and($voice['channels'][0])->toMatchArray(['name' => 'geral', 'joins' => 2, 'people' => 1, 'xp' => 30]);
});

it('conta entradas em call sem somar as saídas', function (): void {
    $alice = dcIdentity('Alice');

    Voice::factory()->joined()->create(['external_identity_id' => $alice->id, 'channel_name' => 'geral', 'occurred_at' => '2026-06-02']);
    Voice::factory()->left()->create(['external_identity_id' => $alice->id, 'channel_name' => 'geral', 'occurred_at' => '2026-06-02']);

    $voice = dcSlide(($this->collect)(), 'discord.voice_board');

    expect($voice['joins'])->toBe(1)
        ->and($voice['channels'][0]['joins'])->toBe(1);
});

it('agrupa salas temporárias de mesmo nome e diz quantas foram', function (): void {
    $alice = dcIdentity('Alice');

    foreach (['sala-1', 'sala-2', 'sala-3'] as $roomId) {
        Voice::factory()->create([
            'external_identity_id' => $alice->id,
            'channel_name' => 'Trabalho',
            'channel_id' => $roomId,
            'occurred_at' => '2026-06-02',
        ]);
    }

    $voice = dcSlide(($this->collect)(), 'discord.voice_board');

    expect($voice['channels'])->toHaveCount(1)
        ->and($voice['channels'][0])->toMatchArray(['name' => 'Trabalho', 'rooms' => 3, 'joins' => 3]);
});

it('separa quem tirou XP de quem só passou pela call', function (): void {
    $comXp = dcIdentity('ComXp');
    $semXp = dcIdentity('SemXp');

    Voice::factory()->create(['external_identity_id' => $comXp->id, 'channel_name' => 'geral', 'obtained_experience' => 40, 'occurred_at' => '2026-06-02']);
    Voice::factory()->create(['external_identity_id' => $semXp->id, 'channel_name' => 'ausente', 'obtained_experience' => 0, 'occurred_at' => '2026-06-02']);

    $voice = dcSlide(($this->collect)(), 'discord.voice_board');

    expect($voice['participants'])->toBe(2)
        ->and($voice['earners'])->toBe(1);
});

it('rankeia quem mais viveu no voice por XP, com entradas e canais distintos', function (): void {
    $alice = dcIdentity('Alice');
    $bob = dcIdentity('Bob');

    Voice::factory()->create(['external_identity_id' => $alice->id, 'channel_name' => 'geral', 'obtained_experience' => 30, 'occurred_at' => '2026-06-02']);
    Voice::factory()->create(['external_identity_id' => $alice->id, 'channel_name' => 'estudos', 'obtained_experience' => 30, 'occurred_at' => '2026-06-03']);
    Voice::factory()->create(['external_identity_id' => $bob->id, 'channel_name' => 'geral', 'obtained_experience' => 10, 'occurred_at' => '2026-06-02']);

    $voice = dcSlide(($this->collect)(), 'discord.voice_board');

    expect($voice['people'][0])->toMatchArray(['name' => 'Alice', 'xp' => 60, 'joins' => 2, 'channels' => 2])
        ->and($voice['people'][1])->toMatchArray(['name' => 'Bob', 'xp' => 10, 'channels' => 1]);
});

it('devolve as 24 horas do histograma, inclusive as vazias', function (): void {
    $alice = dcIdentity('Alice');

    Voice::factory()->create(['external_identity_id' => $alice->id, 'channel_name' => 'geral', 'occurred_at' => '2026-06-02']);

    $voice = dcSlide(($this->collect)(), 'discord.voice_board');

    expect($voice['hours'])->toHaveCount(24)
        ->and(array_column($voice['hours'], 'hour'))->toBe(range(0, 23))
        ->and(array_sum(array_column($voice['hours'], 'joins')))->toBe(1);
});

it('aponta o dia mais movimentado do recorte', function (): void {
    $alice = dcIdentity('Alice');

    Voice::factory()->create(['external_identity_id' => $alice->id, 'channel_name' => 'geral', 'occurred_at' => '2026-06-02 15:00:00']);
    Voice::factory()->count(3)->create(['external_identity_id' => $alice->id, 'channel_name' => 'geral', 'occurred_at' => '2026-06-04 15:00:00']);

    $voice = dcSlide(($this->collect)(), 'discord.voice_board');

    expect($voice['peak'])->toMatchArray(['date' => '04/06', 'joins' => 3]);
});

it('conta novos membros e boosts pelo occurred_at', function (): void {
    $a = dcIdentity('A');
    $b = dcIdentity('B');
    $c = dcIdentity('C');

    dcMembership($a->id, 'user_join', '2026-06-02');
    dcMembership($b->id, 'user_join', '2026-06-03');
    dcMembership($c->id, 'boost_tier_1', '2026-06-03');
    dcMembership($a->id, 'user_join', '2026-05-01');

    $newMembers = dcSlide(($this->collect)(), 'discord.new_members');

    expect($newMembers['joins'])->toBe(2)
        ->and($newMembers['boosts'])->toBe(1);
});

it('agrega reações do recorte por emoji, escopando pelas mensagens do período', function (): void {
    $alice = dcIdentity('Alice');

    $inPeriod = Message::factory()->create(['external_identity_id' => $alice->id, 'sent_at' => '2026-06-02', 'reactions_total' => 8]);
    $outside = Message::factory()->create(['external_identity_id' => $alice->id, 'sent_at' => '2026-05-01', 'reactions_total' => 50]);

    dcReaction($inPeriod->id, 'fire', 3);
    dcReaction($inPeriod->id, 'heart', 5, emojiId: '123');
    dcReaction($outside->id, 'tada', 50);

    $reactions = dcSlide(($this->collect)(), 'discord.reactions');

    expect($reactions['total'])->toBe(8)
        ->and($reactions['emojis'][0])->toMatchArray(['name' => 'heart', 'count' => 5, 'custom' => true])
        ->and($reactions['emojis'][1])->toMatchArray(['name' => 'fire', 'count' => 3, 'custom' => false]);
});

it('expõe os chips do cover só para o que teve dado', function (): void {
    $alice = dcIdentity('Alice');
    Message::factory()->create(['external_identity_id' => $alice->id, 'sent_at' => '2026-06-02']);

    $result = ($this->collect)();
    $labels = collect($result->headline->metrics)->map(fn ($metric): string => $metric->label)->all();

    expect($result->label)->toBe('Discord')
        ->and($labels)->toBe(['Mensagens']);
});

/**
 * Curadoria (Fase 3): a fonte se descreve para o Deck Builder e honra as
 * exclusions no collect — o que é excluído sai dos slides E dos números.
 */
it('descreve o catálogo de slides sem tocar o dado', function (): void {
    $catalog = new DiscordSource()->slideCatalog();

    expect(collect($catalog)->pluck('kind')->all())
        ->toBe(['discord.voice_board', 'discord.messages', 'discord.new_members', 'discord.reactions', 'discord.top_message'])
        ->and($catalog[0]->label)->toBe('Voz');
});

it('oferece mensagens reagidas e pessoas do recorte como candidatos a exclusion', function (): void {
    $alice = dcIdentity('Alice');

    $top = Message::factory()->create(['external_identity_id' => $alice->id, 'sent_at' => '2026-06-02', 'reactions_total' => 9, 'content' => 'compre seguidores']);
    Message::factory()->create(['external_identity_id' => $alice->id, 'sent_at' => '2026-06-03', 'reactions_total' => 0]);
    Message::factory()->create(['external_identity_id' => $alice->id, 'sent_at' => '2026-05-01', 'reactions_total' => 99]);

    $candidates = new DiscordSource()->exclusionCandidates(Period::of($this->since, $this->until));

    $items = collect($candidates)->filter(fn ($candidate): bool => $candidate->kind === ExclusionKind::Item);
    $people = collect($candidates)->filter(fn ($candidate): bool => $candidate->kind === ExclusionKind::Person);

    // Só o que o deck exibe com conteúdo (reagidas) e dentro do recorte.
    expect($items->pluck('ref')->all())->toBe(['message:'.$top->id])
        ->and($items->first()->label)->toBe('compre seguidores')
        ->and($people->pluck('ref')->all())->toBe(['member:'.$alice->id])
        ->and($people->first()->label)->toBe('Alice');
});

it('exclusion de mensagem some do destaque e dos números', function (): void {
    $alice = dcIdentity('Alice');

    $spam = Message::factory()->create(['external_identity_id' => $alice->id, 'sent_at' => '2026-06-02', 'reactions_total' => 30, 'content' => 'scam']);
    Message::factory()->create(['external_identity_id' => $alice->id, 'sent_at' => '2026-06-03', 'reactions_total' => 4, 'content' => 'mensagem boa']);

    $result = new DiscordSource()->collect(
        Period::of($this->since, $this->until),
        new SourceFilters(exclusions: ['message:'.$spam->id]),
    );

    expect(dcSlide($result, 'discord.messages')['total'])->toBe(1)
        ->and(dcSlide($result, 'discord.top_message')['messages'][0]['content'])->toBe('mensagem boa');
});

it('exclusion de pessoa some das mensagens e do board de voz', function (): void {
    $alice = dcIdentity('Alice');
    $spammer = dcIdentity('Spammer');

    Message::factory()->create(['external_identity_id' => $alice->id, 'sent_at' => '2026-06-02']);
    Message::factory()->create(['external_identity_id' => $spammer->id, 'sent_at' => '2026-06-02']);
    Voice::factory()->create(['external_identity_id' => $alice->id, 'channel_name' => 'geral', 'obtained_experience' => 10, 'occurred_at' => '2026-06-02']);
    Voice::factory()->create(['external_identity_id' => $spammer->id, 'channel_name' => 'geral', 'obtained_experience' => 90, 'occurred_at' => '2026-06-02']);

    $result = new DiscordSource()->collect(
        Period::of($this->since, $this->until),
        new SourceFilters(exclusions: ['member:'.$spammer->id]),
    );

    expect(dcSlide($result, 'discord.messages')['total'])->toBe(1)
        ->and(dcSlide($result, 'discord.messages')['chatters'])->toHaveCount(1)
        ->and(dcSlide($result, 'discord.voice_board')['participants'])->toBe(1)
        ->and(dcSlide($result, 'discord.voice_board')['xp'])->toBe(10);
});

it('não monta painel de voz num recorte sem voz', function (): void {
    // O painel inteiro depende de os agregados crus lerem ZERO quando não há
    // linha nenhuma. Lidos como qualquer outra coisa, o portão abriria e o slide
    // entraria no deck sem participante, sem canal e com um dia de pico que
    // ninguém viveu.
    dcIdentity('Alice');

    Message::factory()->create([
        'external_identity_id' => dcIdentity('Bob')->id,
        'sent_at' => '2026-06-02 15:00:00',
    ]);

    $result = ($this->collect)();

    expect(dcSlide($result, 'discord.voice_board'))->toBeEmpty()
        // O recorte tem dado: é o painel de VOZ que não tem por que existir.
        ->and(dcSlide($result, 'discord.messages'))->not->toBeEmpty();
});
