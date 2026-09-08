<?php

declare(strict_types=1);

namespace He4rt\Activity\Database\Seeders;

use Carbon\CarbonImmutable;
use He4rt\Activity\Message\Enums\MembershipEventKind;
use He4rt\Activity\Message\Enums\MessageKind;
use He4rt\Activity\Message\Enums\MessageSourceKind;
use He4rt\Activity\Message\Models\MembershipEvent;
use He4rt\Activity\Message\Models\Message;
use He4rt\Activity\Reaction\Models\Reaction;
use He4rt\Activity\Retrospective\DiscordSource;
use He4rt\Activity\Voice\Enums\VoicePresenceEnum;
use He4rt\Activity\Voice\Models\Voice;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Popula a fonte Discord da retrospectiva: identidades externas com username
 * resolvível, mensagens com reações coerentes, presença em voz e eventos de
 * entrada/boost.
 *
 * Não cria retrospectivas — quem monta as edições é o RetrospectiveSeeder do
 * community.
 *
 * Mensagens, reações, voz e membership vão por insert em lote: são ~4.000 linhas
 * e cada Message::factory() arrastaria uma ExternalIdentity e dois User novos.
 */
final class DiscordRetrospectiveSeeder extends Seeder
{
    /**
     * Membros da comunidade com pesos desiguais — é o que faz "quem mais falou"
     * ter topo e cauda em vez de um platô.
     *
     * @var list<array{handle: string, name: string, weight: int}>
     */
    private const array MEMBERS = [
        ['handle' => 'danielhe4rt', 'name' => 'Daniel Reis', 'weight' => 24],
        ['handle' => 'kaster', 'name' => 'Kaster', 'weight' => 20],
        ['handle' => 'clinton', 'name' => 'Clinton Rocha', 'weight' => 18],
        ['handle' => 'jotaonemore', 'name' => 'João Pedro', 'weight' => 15],
        ['handle' => 'marcelab', 'name' => 'Marcela Bomfim', 'weight' => 13],
        ['handle' => 'lucasnasc', 'name' => 'Lucas Nascimento', 'weight' => 12],
        ['handle' => 'anaparaiso', 'name' => 'Ana Paraíso', 'weight' => 11],
        ['handle' => 'thiagodev', 'name' => 'Thiago Alves', 'weight' => 10],
        ['handle' => 'brunacastro', 'name' => 'Bruna Castro', 'weight' => 10],
        ['handle' => 'rafaelmelo', 'name' => 'Rafael Melo', 'weight' => 9],
        ['handle' => 'giselletech', 'name' => 'Giselle Prado', 'weight' => 8],
        ['handle' => 'pedrolima', 'name' => 'Pedro Lima', 'weight' => 8],
        ['handle' => 'juliaoliver', 'name' => 'Júlia Oliveira', 'weight' => 7],
        ['handle' => 'vinizago', 'name' => 'Vinícius Zago', 'weight' => 7],
        ['handle' => 'camilaqa', 'name' => 'Camila Ferraz', 'weight' => 6],
        ['handle' => 'erickson', 'name' => 'Erickson Gomes', 'weight' => 6],
        ['handle' => 'natalyabraga', 'name' => 'Natália Braga', 'weight' => 5],
        ['handle' => 'guilhermesr', 'name' => 'Guilherme Souza', 'weight' => 5],
        ['handle' => 'leticiam', 'name' => 'Letícia Martins', 'weight' => 5],
        ['handle' => 'fabiosouza', 'name' => 'Fábio Souza', 'weight' => 4],
        ['handle' => 'renatoq', 'name' => 'Renato Queiroz', 'weight' => 4],
        ['handle' => 'isabelaramos', 'name' => 'Isabela Ramos', 'weight' => 4],
        ['handle' => 'matheusfront', 'name' => 'Matheus Prado', 'weight' => 3],
        ['handle' => 'sarahdevops', 'name' => 'Sarah Lopes', 'weight' => 3],
        ['handle' => 'joaquimt', 'name' => 'Joaquim Torres', 'weight' => 3],
        ['handle' => 'helenagomes', 'name' => 'Helena Gomes', 'weight' => 3],
        ['handle' => 'diegocampos', 'name' => 'Diego Campos', 'weight' => 2],
        ['handle' => 'priscilamt', 'name' => 'Priscila Matos', 'weight' => 2],
        ['handle' => 'wesleyalmeida', 'name' => 'Wesley Almeida', 'weight' => 2],
        ['handle' => 'tainaraf', 'name' => 'Tainara Freitas', 'weight' => 2],
        ['handle' => 'otavionunes', 'name' => 'Otávio Nunes', 'weight' => 1],
        ['handle' => 'beatrizsilva', 'name' => 'Beatriz Silva', 'weight' => 1],
        ['handle' => 'caiomendes', 'name' => 'Caio Mendes', 'weight' => 1],
        ['handle' => 'lariribeiro', 'name' => 'Larissa Ribeiro', 'weight' => 1],
    ];

    /** @var list<array{handle: string, name: string, weight: int}> */
    private const array BOTS = [
        ['handle' => 'he4rt-bot', 'name' => 'He4rt Bot', 'weight' => 9],
        ['handle' => 'mee6', 'name' => 'MEE6', 'weight' => 4],
    ];

    /** @var list<array{id: string, name: string}> */
    private const array TEXT_CHANNELS = [
        ['id' => '901000000000000001', 'name' => 'geral'],
        ['id' => '901000000000000002', 'name' => 'programacao'],
        ['id' => '901000000000000003', 'name' => 'duvidas'],
        ['id' => '901000000000000004', 'name' => 'carreira'],
        ['id' => '901000000000000005', 'name' => 'vagas'],
        ['id' => '901000000000000006', 'name' => 'projetos'],
        ['id' => '901000000000000007', 'name' => 'off-topic'],
        ['id' => '901000000000000008', 'name' => 'pets'],
    ];

    /** @var list<string> */
    private const array VOICE_CHANNELS = [
        'Coworking 1',
        'Coworking 2',
        'Estudos',
        'Pair Programming',
        'Café e Bug',
        'Live / Stream',
        'Games',
    ];

    /** @var list<string> */
    private const array CONTENT = [
        'bom dia, gente ☕',
        'alguém já usou ScyllaDB com Laravel em produção?',
        'subiu a v5 do Filament, quem topa migrar o painel comigo?',
        'gente, o deploy caiu ou é só aqui?',
        'acabei de mergear o PR da retrospectiva, tá no ar 🎉',
        'dica: `php artisan about` mostra a config toda resolvida',
        'quem vai no meetup de sexta?',
        'consegui minha primeira vaga como dev, obrigado a vocês 🥹',
        'esse bug de timezone tá me deixando louco, -3h em tudo',
        'to estudando Pest 4 e os browser tests são muito bons',
        'alguém tem material bom de Postgres pra indicar?',
        'terminei o desafio da semana, bora revisar?',
        'olha o gato que adotei ontem 🐱',
        'PR aberto, review quando puderem 🙏',
        'gente lembrem de preencher o formulário do evento',
        'como vocês organizam módulo por contexto em monorepo?',
        'a call de ontem rendeu, valeu quem apareceu',
        'esse rector é mágico, arrumou 40 arquivos sozinho',
        'boa noite pessoal, bom descanso',
        'só eu que acho jsonb melhor que tabela nova em 80% dos casos?',
        'consegui reduzir a query de 4s pra 80ms com índice composto',
        'quem quiser pair programming hoje à noite, tô na Coworking 1',
        'lançamos o onboarding novo, feedback é bem-vindo',
        'aprendi hoje que `whereBelongsTo` existe e mudou minha vida',
        'algum livro bom de arquitetura pra quem tá começando?',
        'gente, cuidado com link estranho na DM, tem gente aplicando golpe',
        'o CI tá vermelho no main, alguém olhando?',
        'passei na entrevista técnica!! 🚀',
        'fiz meu primeiro contribuição open source aqui 💜',
        'alguém sabe por que o bot não responde no canal novo?',
        'sugestão: canal só pra vagas de júnior',
        'olha esse benchmark que fiz de Livewire vs Inertia',
        'tô com dúvida em multi-tenancy no Filament, alguém ajuda?',
        'bora fazer um projeto em grupo esse mês?',
        'obrigado pela review, aprendi bastante',
        'a doc do módulo tá desatualizada, abri issue',
        'quem tá na live? o áudio tá ok?',
        'terminei a trilha de PHP, próxima é Go',
        'esse design system ficou muito bom, parabéns',
        'gente o XP de voz não tá contando pra mim',
        'boa tarde, alguém disponível pra tirar dúvida de SQL?',
        'consegui rodar o bot local sem Docker, escrevi um passo a passo',
        'aviso: manutenção no banco hoje às 23h',
        'quem indica curso de Kubernetes?',
        'meu setup novo chegou, agora sim 🖥️',
        'alguém aqui já usou Saloon pra integração?',
        'a retrospectiva do ano ficou linda, parabéns time',
        'to montando meu portfólio, aceito crítica sincera',
        'esse teste tá flaky, já tentei 3 vezes',
        'feliz aniversário He4rt 💜',
    ];

    /** @var list<array{key: string, name: string, id: string|null}> */
    private const array EMOJIS = [
        ['key' => '💜', 'name' => 'purple_heart', 'id' => null],
        ['key' => '🚀', 'name' => 'rocket', 'id' => null],
        ['key' => '🎉', 'name' => 'tada', 'id' => null],
        ['key' => '👏', 'name' => 'clap', 'id' => null],
        ['key' => '😂', 'name' => 'joy', 'id' => null],
        ['key' => '👀', 'name' => 'eyes', 'id' => null],
        ['key' => '🔥', 'name' => 'fire', 'id' => null],
        ['key' => '☕', 'name' => 'coffee', 'id' => null],
        ['key' => 'he4rtlove:820100000000000001', 'name' => 'he4rtlove', 'id' => '820100000000000001'],
        ['key' => 'pog:820100000000000002', 'name' => 'pog', 'id' => '820100000000000002'],
        ['key' => 'kekw:820100000000000003', 'name' => 'kekw', 'id' => '820100000000000003'],
        ['key' => 'thonkang:820100000000000004', 'name' => 'thonkang', 'id' => '820100000000000004'],
    ];

    /**
     * Conteúdo que o slide de destaque exibiria na íntegra — chega ao topo por
     * reação e é exatamente o que o operador precisa poder esconder.
     */
    private const string SCAM_CONTENT = '🚨 FREE NITRO PRA TODOS! clique aqui: discordgift-nitro.ru/he4rt @everyone';

    /** @var list<string> */
    private array $baits = [];

    /**
     * Refs plantados para o Deck Builder ter o que curar (a mensagem de golpe e o
     * membro que só faz spam).
     *
     * @return array<string, list<string>>
     */
    public function exclusionBaits(): array
    {
        return [resolve(DiscordSource::class)->key() => $this->baits];
    }

    /**
     * O recorte `spotlight` é onde as iscas de curadoria são plantadas: precisa
     * ser um intervalo que caia dentro das edições recentes, senão o picker do
     * Deck Builder não tem o que oferecer. Quem manda é o orquestrador — o padrão
     * é o último mês fechado.
     */
    public function run(
        ?string $since = null,
        ?string $until = null,
        ?string $spotlightSince = null,
        ?string $spotlightUntil = null,
    ): void {
        $window = $this->window($since, $until);
        $spotlight = $this->window(
            $spotlightSince ?? (string) CarbonImmutable::now()->startOfMonth()->subMonth(),
            $spotlightUntil ?? (string) CarbonImmutable::now()->startOfMonth()->subSecond(),
        );

        $members = $this->identities(self::MEMBERS);
        $bots = $this->identities(self::BOTS);

        $messages = $this->messages($window, $spotlight, $members, $bots);

        $this->insertInto(Message::class, $messages['messages']);
        $this->insertInto(Reaction::class, $messages['reactions']);
        $this->insertInto(Voice::class, $this->voice($window, $members));
        $this->insertInto(MembershipEvent::class, $this->membership($window, $members));

        $this->command?->getOutput()->writeln(sprintf(
            '  <fg=gray>Discord:</> %d mensagens · %d reações · %d membros (+%d bots)',
            count($messages['messages']),
            count($messages['reactions']),
            count($members),
            count($bots),
        ));
    }

    /**
     * Uma identidade externa do Discord por pessoa, com o username no metadata —
     * é de lá que o DiscordSource resolve o nome de exibição dos rankings.
     *
     * @param  list<array{handle: string, name: string, weight: int}>  $people
     * @return list<array{id: string, weight: int}>
     */
    private function identities(array $people): array
    {
        $identities = [];

        foreach ($people as $person) {
            // `users.username` é único e o BaseSeeder já cria o admin
            // (danielhe4rt): reaproveitar em vez de recriar deixa o operador
            // logado aparecendo na própria retrospectiva.
            $user = User::query()->firstWhere('username', $person['handle'])
                ?? User::factory()->create([
                    'username' => $person['handle'],
                    'name' => $person['name'],
                    'email' => $person['handle'].'@he4rt.dev',
                ]);

            $identity = ExternalIdentity::factory()->create([
                'model_type' => $user->getMorphClass(),
                'model_id' => $user->getKey(),
                'connected_by' => $user->getKey(),
                'provider' => IdentityProvider::Discord,
                'external_account_id' => (string) fake()->unique()->numberBetween(100_000_000_000_000_000, 999_999_999_999_999_999),
                'email' => $user->email,
                'metadata' => [
                    'email' => $user->email,
                    'username' => $person['handle'],
                    'global_name' => $person['name'],
                ],
            ]);

            $identities[] = ['id' => $identity->getKey(), 'weight' => $person['weight']];
        }

        return $identities;
    }

    /**
     * Monta mensagens e reações juntas: `reactions_total` e `reactions_count` são
     * colunas desnormalizadas que o DiscordSource lê direto, então precisam bater
     * com as linhas de activity_reactions — construir os dois no mesmo passo é o
     * que garante isso.
     *
     * @param  array{since: CarbonImmutable, until: CarbonImmutable}  $window
     * @param  array{since: CarbonImmutable, until: CarbonImmutable}  $spotlight
     * @param  list<array{id: string, weight: int}>  $members
     * @param  list<array{id: string, weight: int}>  $bots
     * @return array{messages: list<array<string, mixed>>, reactions: list<array<string, mixed>>}
     */
    private function messages(array $window, array $spotlight, array $members, array $bots): array
    {
        $messages = [];
        $reactions = [];
        $providerId = fake()->numberBetween(100_000_000_000_000_000, 800_000_000_000_000_000);

        foreach (range(1, 2_400) as $index) {
            $isBot = fake()->boolean(7);
            $author = $this->weighted($isBot ? $bots : $members);

            // Uma fatia sem source_kind reproduz as linhas históricas do ETL: o
            // filtro de bots precisa mantê-las (whereNull), não derrubá-las.
            $sourceKind = match (true) {
                $isBot => MessageSourceKind::Bot,
                $index % 12 === 0 => null,
                default => MessageSourceKind::User,
            };

            $id = Str::orderedUuid()->toString();
            $reacted = fake()->boolean(11);
            $rows = $reacted ? $this->reactionsFor($id) : [];

            $messages[] = $this->message(
                id: $id,
                authorId: $author['id'],
                providerId: (string) ++$providerId,
                content: fake()->randomElement(self::CONTENT),
                sentAt: $this->moment($window),
                sourceKind: $sourceKind,
                pinned: fake()->boolean(1),
                reactions: $rows,
            );

            $reactions = [...$reactions, ...$rows];
        }

        [$scamMessage, $scamReactions] = $this->scam($spotlight, $members, (string) ++$providerId);
        $messages[] = $scamMessage;
        $reactions = [...$reactions, ...$scamReactions];

        foreach ($this->spam($window, $spotlight, $members, $providerId) as $row) {
            $messages[] = $row;
        }

        return ['messages' => $messages, 'reactions' => $reactions];
    }

    /**
     * A mensagem de golpe, campeã de reação: é o topo de messageCandidates() e do
     * slide de destaque ao mesmo tempo.
     *
     * @param  array{since: CarbonImmutable, until: CarbonImmutable}  $spotlight
     * @param  list<array{id: string, weight: int}>  $members
     * @return array{0: array<string, mixed>, 1: list<array<string, mixed>>}
     */
    private function scam(array $spotlight, array $members, string $providerId): array
    {
        $id = Str::orderedUuid()->toString();
        // Piso acima do teto de qualquer mensagem comum (4 emojis × 15): garante o
        // topo por reação em vez de depender de sorte no sorteio.
        $reactions = $this->reactionsFor($id, floor: 80);

        $this->baits[] = 'message:'.$id;

        return [
            $this->message(
                id: $id,
                authorId: $members[count($members) - 1]['id'],
                providerId: $providerId,
                content: self::SCAM_CONTENT,
                sentAt: $this->moment($spotlight),
                sourceKind: MessageSourceKind::User,
                pinned: false,
                reactions: $reactions,
            ),
            $reactions,
        ];
    }

    /**
     * O membro que só faz volume: chega ao topo de memberCandidates() e distorce
     * "quem mais falou" até ser excluído.
     *
     * Um terço do volume vai concentrado no `spotlight`: sem isso o spam se
     * dilui em 13 meses e o membro não entra no topo-20 de um recorte curto,
     * deixando a isca invisível justo na edição que já a exclui.
     *
     * @param  array{since: CarbonImmutable, until: CarbonImmutable}  $window
     * @param  array{since: CarbonImmutable, until: CarbonImmutable}  $spotlight
     * @param  list<array{id: string, weight: int}>  $members
     * @return list<array<string, mixed>>
     */
    private function spam(array $window, array $spotlight, array $members, int $providerId): array
    {
        $author = $members[count($members) - 2];
        $this->baits[] = 'member:'.$author['id'];

        $rows = [];

        foreach (range(1, 240) as $index) {
            $rows[] = $this->message(
                id: Str::orderedUuid()->toString(),
                authorId: $author['id'],
                providerId: (string) ++$providerId,
                content: fake()->randomElement([
                    'compra seguidores aqui 👉 link na bio',
                    'GANHE DINHEIRO RÁPIDO, chama no pv',
                    'promo de curso, últimas vagas!!!',
                ]),
                sentAt: $this->moment($index % 3 === 0 ? $spotlight : $window),
                sourceKind: MessageSourceKind::User,
                pinned: false,
                reactions: [],
            );
        }

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $reactions
     * @return array<string, mixed>
     */
    private function message(
        string $id,
        string $authorId,
        string $providerId,
        string $content,
        CarbonImmutable $sentAt,
        ?MessageSourceKind $sourceKind,
        bool $pinned,
        array $reactions,
    ): array {
        return [
            'id' => $id,
            'external_identity_id' => $authorId,
            'provider_message_id' => $providerId,
            'channel_id' => fake()->randomElement(self::TEXT_CHANNELS)['id'],
            'content' => $content,
            'obtained_experience' => fake()->numberBetween(1, 12),
            'sent_at' => $sentAt,
            'kind' => MessageKind::Default->value,
            'source_kind' => $sourceKind?->value,
            'is_pinned' => $pinned,
            'mentions_everyone' => false,
            'mention_role_count' => 0,
            'reactions_count' => count($reactions),
            'reactions_total' => (int) array_sum(array_column($reactions, 'count')),
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Emojis distintos por mensagem: activity_reactions é única em
     * (reactable_type, reactable_id, emoji_key).
     *
     * @return list<array<string, mixed>>
     */
    private function reactionsFor(string $messageId, int $floor = 1): array
    {
        /** @var list<array{key: string, name: string, id: string|null}> $picked */
        $picked = fake()->randomElements(self::EMOJIS, fake()->numberBetween(1, 4));

        return array_map(
            function (array $emoji) use ($messageId, $floor): array {
                $count = fake()->numberBetween($floor, $floor + 14);

                return [
                    'id' => Str::orderedUuid()->toString(),
                    'reactable_type' => 'message',
                    'reactable_id' => $messageId,
                    'emoji_key' => $emoji['key'],
                    'emoji_id' => $emoji['id'],
                    'emoji_name' => $emoji['name'],
                    'count' => $count,
                    'count_normal' => $count,
                    'count_burst' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            },
            $picked,
        );
    }

    /**
     * @param  array{since: CarbonImmutable, until: CarbonImmutable}  $window
     * @param  list<array{id: string, weight: int}>  $members
     * @return list<array<string, mixed>>
     */
    private function voice(array $window, array $members): array
    {
        $rows = [];
        $providerId = fake()->numberBetween(100_000_000_000_000_000, 800_000_000_000_000_000);

        foreach (range(1, 1_300) as $ignored) {
            $member = $this->weighted($members);
            $joined = fake()->boolean(60);

            $rows[] = [
                'external_identity_id' => $member['id'],
                'provider_message_id' => (string) ++$providerId,
                'channel_name' => fake()->randomElement(self::VOICE_CHANNELS),
                'channel_id' => (string) fake()->numberBetween(902_000_000_000_000_000, 902_999_999_999_999_999),
                'state' => $joined ? VoicePresenceEnum::Joined->value : VoicePresenceEnum::Left->value,
                'obtained_experience' => $joined ? fake()->numberBetween(5, 180) : 0,
                'occurred_at' => $this->moment($window),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return $rows;
    }

    /**
     * @param  array{since: CarbonImmutable, until: CarbonImmutable}  $window
     * @param  list<array{id: string, weight: int}>  $members
     * @return list<array<string, mixed>>
     */
    private function membership(array $window, array $members): array
    {
        $rows = [];
        $providerId = fake()->numberBetween(100_000_000_000_000_000, 800_000_000_000_000_000);

        // Entradas não seguem o peso de conversa: quem acabou de chegar quase não
        // fala ainda, então o sorteio aqui é uniforme.
        foreach (range(1, 210) as $ignored) {
            $rows[] = $this->membershipRow(
                MembershipEventKind::UserJoin,
                fake()->randomElement($members)['id'],
                (string) ++$providerId,
                $this->moment($window),
            );
        }

        foreach (range(1, 42) as $ignored) {
            $rows[] = $this->membershipRow(
                fake()->randomElement([
                    MembershipEventKind::Boost,
                    MembershipEventKind::BoostTier1,
                    MembershipEventKind::BoostTier2,
                    MembershipEventKind::BoostTier3,
                ]),
                $this->weighted($members)['id'],
                (string) ++$providerId,
                $this->moment($window),
            );
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private function membershipRow(
        MembershipEventKind $kind,
        string $identityId,
        string $providerId,
        CarbonImmutable $occurredAt,
    ): array {
        return [
            'id' => Str::orderedUuid()->toString(),
            'external_identity_id' => $identityId,
            'kind' => $kind->value,
            'occurred_at' => $occurredAt,
            'provider_message_id' => $providerId,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Reaction e MembershipEvent não têm factory (nem `HasFactory`), então o
     * insert sai pelo query builder do próprio model — o nome da tabela continua
     * vindo do `#[Table]`, sem string solta no seeder.
     *
     * @param  class-string<Model>  $model
     * @param  list<array<string, mixed>>  $rows
     */
    private function insertInto(string $model, array $rows): void
    {
        foreach (array_chunk($rows, 500) as $chunk) {
            $model::query()->insert($chunk);
        }
    }

    /**
     * @template T of array{weight: int}
     *
     * @param  list<T>  $items
     * @return T
     */
    private function weighted(array $items): array
    {
        $total = array_sum(array_column($items, 'weight'));
        $draw = fake()->numberBetween(1, $total);

        foreach ($items as $item) {
            $draw -= $item['weight'];

            if ($draw <= 0) {
                return $item;
            }
        }

        return $items[0];
    }

    /**
     * @param  array{since: CarbonImmutable, until: CarbonImmutable}  $window
     */
    private function moment(array $window): CarbonImmutable
    {
        $span = $window['until']->getTimestamp() - $window['since']->getTimestamp();
        $bias = fake()->randomFloat(4, 0, 1) ** 0.65;

        return $window['since']->addSeconds((int) round($span * $bias));
    }

    /**
     * @return array{since: CarbonImmutable, until: CarbonImmutable}
     */
    private function window(?string $since, ?string $until): array
    {
        return [
            'since' => $since === null
                ? CarbonImmutable::now()->startOfMonth()->subMonths(13)
                : CarbonImmutable::parse($since),
            'until' => $until === null
                ? CarbonImmutable::now()->subDay()
                : CarbonImmutable::parse($until),
        ];
    }
}
