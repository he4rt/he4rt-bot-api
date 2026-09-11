<?php

declare(strict_types=1);

use Carbon\CarbonInterface;
use He4rt\Activity\Message\Enums\MessageKind;
use He4rt\Activity\Message\Enums\MessageSourceKind;
use He4rt\Activity\Message\Models\MembershipEvent;
use He4rt\Activity\Message\Models\Message;
use He4rt\Activity\Message\Models\MessageAttachment;
use He4rt\Activity\Message\Models\MessageEmbed;
use He4rt\Activity\Message\Models\MessageMention;
use He4rt\Activity\Message\Models\MessageThread;
use He4rt\Activity\Moderation\Enums\ModerationType;
use He4rt\Activity\Moderation\Models\ModerationEvent;
use He4rt\Activity\Reaction\Models\Reaction;
use He4rt\Activity\Voice\Models\Voice;
use He4rt\Identity\ExternalIdentity\Data\ClientAccessManager;
use He4rt\Identity\ExternalIdentity\Enums\CredentialsType;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use He4rt\IntegrationDiscord\ETL\Actions\ImportDiscordMessageAction;
use He4rt\IntegrationDiscord\ETL\Actions\ImportDiscordModerationEventAction;
use He4rt\IntegrationDiscord\ETL\Actions\ImportDiscordReactionsAction;
use He4rt\IntegrationDiscord\ETL\Actions\ImportDiscordVoiceLogAction;
use He4rt\IntegrationDiscord\ETL\DTOs\DiscordMessageDTO;
use He4rt\IntegrationDiscord\ETL\DTOs\DiscordMessageReactionDTO;
use He4rt\IntegrationDiscord\ETL\DTOs\DiscordModerationEventDTO;
use He4rt\IntegrationDiscord\ETL\DTOs\DiscordVoiceLogDTO;
use Illuminate\Support\Facades\Crypt;
use Ramsey\Uuid\Uuid;

// ── Helpers ──────────────────────────────────────────────────────

function discordMessage(array $overrides = []): array
{
    $default = [
        'type' => 0,
        'content' => 'Olá, mundo!',
        'id' => '541468820355940381',
        'channel_id' => '541443469642563585',
        'timestamp' => '2019-02-03T04:03:46.777000+00:00',
        'edited_timestamp' => null,
        'author' => [
            'id' => '253642464697516033',
            'username' => 'letsch1',
            'global_name' => 'letsch',
            'avatar' => 'abc123',
            'bot' => false,
        ],
        'mentions' => [],
        'mention_roles' => [],
        'attachments' => [],
        'embeds' => [],
        'reactions' => [],
        'pinned' => false,
        'flags' => 0,
    ];

    return array_replace_recursive($default, $overrides);
}

function dynoVoiceLog(string $userId, string $channelId, string $action = 'joined'): array
{
    return discordMessage([
        'id' => (string) fake()->unique()->numberBetween(100_000, 999_999),
        'content' => '',
        'author' => ['id' => '155149108183695360', 'username' => 'Dyno', 'bot' => true],
        'embeds' => [[
            'description' => sprintf('**<@!%s> %s voice channel <#%s>**', $userId, $action, $channelId),
            'color' => $action === 'joined' ? 3_066_993 : 15_158_332,
        ]],
    ]);
}

function dynoModerationLog(string $username, string $discriminator, string $action): array
{
    return discordMessage([
        'id' => (string) fake()->unique()->numberBetween(100_000, 999_999),
        'content' => '',
        'author' => ['id' => '155149108183695360', 'username' => 'Dyno', 'bot' => true],
        'embeds' => [[
            'description' => sprintf('<:dynoSuccess:546395281093034015> ***%s#%s %s***', $username, $discriminator, $action),
        ]],
    ]);
}

function heartdevsModerationLog(string $subjectId, string $moderatorId, string $type, string $reason): array
{
    return discordMessage([
        'id' => (string) fake()->unique()->numberBetween(100_000, 999_999),
        'content' => '',
        'author' => ['id' => '123456789', 'username' => 'heartdevs.com', 'bot' => true],
        'embeds' => [[
            'title' => '🚔 » Punição',
            'fields' => [
                ['name' => '🧔 Usuário punido:', 'value' => sprintf('<@%s>', $subjectId)],
                ['name' => '🧑‍⚖️ Punido por:', 'value' => sprintf('<@%s>', $moderatorId)],
                ['name' => '📄 Tipo:', 'value' => $type],
                ['name' => '📢 Motivo:', 'value' => $reason],
            ],
        ]],
    ]);
}

function createTestIdentity(string $discordId, string $username = 'testuser'): ExternalIdentity
{
    $user = User::query()->firstOrCreate(
        ['username' => $username],
        ['id' => Uuid::uuid4()->toString(), 'name' => $username, 'is_donator' => false],
    );

    return ExternalIdentity::query()->create([
        'provider' => IdentityProvider::Discord,
        'external_account_id' => $discordId,
        'model_type' => (new User)->getMorphClass(),
        'model_id' => $user->id,
        'type' => IdentityProvider::Discord->getType(),
        'credentials_type' => CredentialsType::OAuth2,
        'credentials' => ClientAccessManager::make(),
        'metadata' => ['user' => ['username' => $username, 'discriminator' => '0']],
    ]);
}

// ── DiscordMessageDTO Tests ──────────────────────────────────────

test('DiscordMessageDTO creates from discord dump format', function (): void {
    $raw = discordMessage();
    $dto = DiscordMessageDTO::fromDump($raw);

    expect($dto->discordMessageId)->toBe('541468820355940381')
        ->and($dto->channelId)->toBe('541443469642563585')
        ->and($dto->authorDiscordId)->toBe('253642464697516033')
        ->and($dto->authorUsername)->toBe('letsch1')
        ->and($dto->authorName)->toBe('letsch')
        ->and($dto->isBot)->toBeFalse()
        ->and($dto->content)->toBe('Olá, mundo!')
        ->and($dto->sentAt)->toBe('2019-02-03T04:03:46.777000+00:00');
});

test('DiscordMessageDTO falls back to username when global_name is null', function (): void {
    $raw = discordMessage(['author' => ['global_name' => null]]);
    $dto = DiscordMessageDTO::fromDump($raw);

    expect($dto->authorName)->toBe('letsch1');
});

test('DiscordMessageDTO detects bot messages', function (): void {
    $raw = discordMessage(['author' => ['bot' => true]]);
    $dto = DiscordMessageDTO::fromDump($raw);

    expect($dto->isBot)->toBeTrue();
});

test('DiscordMessageDTO preserves entire message in metadata', function (): void {
    $raw = discordMessage();
    $dto = DiscordMessageDTO::fromDump($raw);

    expect($dto->metadata)->toBe($raw);
});

test('DiscordMessageDTO::toDatabase maps fields and parses sent_at to Carbon', function (): void {
    $dto = DiscordMessageDTO::fromDump(discordMessage());
    $result = $dto->toDatabase(['external_identity_id' => 1, 'obtained_experience' => 0]);

    expect($result['provider_message_id'])->toBe('541468820355940381')
        ->and($result['channel_id'])->toBe('541443469642563585')
        ->and($result['content'])->toBe('Olá, mundo!')
        ->and($result['sent_at'])->toBeInstanceOf(CarbonInterface::class)
        ->and($result['metadata'])->toBeArray()
        ->and($result['external_identity_id'])->toBe(1)
        ->and($result['obtained_experience'])->toBe(0);
});

// ── DiscordMessageReactionDTO Tests ──────────────────────────────

test('DiscordMessageReactionDTO returns empty array for message without reactions', function (): void {
    $raw = discordMessage(['reactions' => []]);
    $reactions = DiscordMessageReactionDTO::fromDumpMessage($raw);

    expect($reactions)->toBeEmpty();
});

test('DiscordMessageReactionDTO extracts unicode emoji with null emoji_id', function (): void {
    $raw = discordMessage(['reactions' => [[
        'emoji' => ['id' => null, 'name' => '👍'],
        'count' => 3,
        'count_details' => ['burst' => 0, 'normal' => 3],
    ]]]);

    $reactions = DiscordMessageReactionDTO::fromDumpMessage($raw);

    expect($reactions)->toHaveCount(1)
        ->and($reactions[0]->emojiId)->toBeNull()
        ->and($reactions[0]->emojiName)->toBe('👍')
        ->and($reactions[0]->count)->toBe(3);
});

test('DiscordMessageReactionDTO extracts custom discord emoji with snowflake id', function (): void {
    $raw = discordMessage(['reactions' => [[
        'emoji' => ['id' => '546395281093034015', 'name' => 'he4rt'],
        'count' => 5,
        'count_details' => ['burst' => 1, 'normal' => 4],
    ]]]);

    $reactions = DiscordMessageReactionDTO::fromDumpMessage($raw);

    expect($reactions)->toHaveCount(1)
        ->and($reactions[0]->emojiId)->toBe('546395281093034015')
        ->and($reactions[0]->emojiName)->toBe('he4rt')
        ->and($reactions[0]->countBurst)->toBe(1)
        ->and($reactions[0]->countNormal)->toBe(4);
});

test('DiscordMessageReactionDTO extracts multiple reactions from a single message', function (): void {
    $raw = discordMessage(['reactions' => [
        ['emoji' => ['id' => null, 'name' => '🔥'], 'count' => 2, 'count_details' => ['burst' => 0, 'normal' => 2]],
        ['emoji' => ['id' => '123', 'name' => 'custom'], 'count' => 1, 'count_details' => ['burst' => 0, 'normal' => 1]],
    ]]);

    $reactions = DiscordMessageReactionDTO::fromDumpMessage($raw);

    expect($reactions)->toHaveCount(2);
});

test('DiscordMessageReactionDTO skips malformed reactions without emoji name', function (): void {
    $raw = discordMessage(['reactions' => [
        ['emoji' => ['id' => null, 'name' => null], 'count' => 1, 'count_details' => ['burst' => 0, 'normal' => 1]],
        ['emoji' => ['id' => null, 'name' => '👍'], 'count' => 1, 'count_details' => ['burst' => 0, 'normal' => 1]],
    ]]);

    $reactions = DiscordMessageReactionDTO::fromDumpMessage($raw);

    expect($reactions)->toHaveCount(1)
        ->and($reactions[0]->emojiName)->toBe('👍');
});

test('DiscordMessageReactionDTO::toDatabase computes emoji_key with u: prefix for unicode', function (): void {
    $dto = new DiscordMessageReactionDTO(emojiId: null, emojiName: '🔥', count: 2, countBurst: 0, countNormal: 2);
    $result = $dto->toDatabase();

    expect($result['emoji_key'])->toBe('u:🔥')
        ->and($result['emoji_id'])->toBeNull()
        ->and($result['emoji_name'])->toBe('🔥');
});

test('DiscordMessageReactionDTO::toDatabase uses emoji_id as key for custom emojis', function (): void {
    $dto = new DiscordMessageReactionDTO(emojiId: '546395281093034015', emojiName: 'he4rt', count: 5, countBurst: 0, countNormal: 5);
    $result = $dto->toDatabase();

    expect($result['emoji_key'])->toBe('546395281093034015');
});

// ── DiscordVoiceLogDTO Tests ─────────────────────────────────────

test('DiscordVoiceLogDTO extracts voice join event from dyno embed', function (): void {
    $raw = dynoVoiceLog('529963308577456128', '452928009964355604', 'joined');
    $dto = DiscordVoiceLogDTO::fromDump($raw);

    expect($dto)->not->toBeNull()
        ->and($dto->userDiscordId)->toBe('529963308577456128')
        ->and($dto->voiceChannelId)->toBe('452928009964355604')
        ->and($dto->action)->toBe('joined');
});

test('DiscordVoiceLogDTO extracts voice leave event from dyno embed', function (): void {
    $raw = dynoVoiceLog('529963308577456128', '452928009964355604', 'left');
    $dto = DiscordVoiceLogDTO::fromDump($raw);

    expect($dto)->not->toBeNull()
        ->and($dto->action)->toBe('left');
});

test('DiscordVoiceLogDTO returns null for non-voice messages', function (): void {
    $raw = discordMessage();
    $dto = DiscordVoiceLogDTO::fromDump($raw);

    expect($dto)->toBeNull();
});

test('DiscordVoiceLogDTO returns null for messages without embeds', function (): void {
    $raw = discordMessage(['embeds' => []]);
    $dto = DiscordVoiceLogDTO::fromDump($raw);

    expect($dto)->toBeNull();
});

test('DiscordVoiceLogDTO::toDatabase maps joined action to joined state', function (): void {
    $raw = dynoVoiceLog('123', '456', 'joined');
    $dto = DiscordVoiceLogDTO::fromDump($raw);

    $result = $dto->toDatabase();

    expect($result['state'])->toBe('joined')
        ->and($result['obtained_experience'])->toBe(0)
        ->and($result['provider_message_id'])->toBe($raw['id'])
        ->and($result['occurred_at'])->toBe($raw['timestamp']);
});

test('DiscordVoiceLogDTO::toDatabase maps left action to left state', function (): void {
    $raw = dynoVoiceLog('123', '456', 'left');
    $dto = DiscordVoiceLogDTO::fromDump($raw);

    $result = $dto->toDatabase();

    expect($result['state'])->toBe('left');
});

// ── DiscordModerationEventDTO Tests ──────────────────────────────

test('DiscordModerationEventDTO extracts ban from dyno embed', function (): void {
    $raw = dynoModerationLog('someuser', '1234', 'was banned');
    $dto = DiscordModerationEventDTO::fromDump($raw);

    expect($dto)->not->toBeNull()
        ->and($dto->type)->toBe(ModerationType::Ban)
        ->and($dto->botDiscordId)->toBe('155149108183695360')
        ->and($dto->subjectUsername)->toBe('someuser')
        ->and($dto->subjectDiscriminator)->toBe('1234')
        ->and($dto->subjectDiscordId)->toBeNull();
});

test('DiscordModerationEventDTO extracts warn from dyno embed', function (): void {
    $raw = dynoModerationLog('anotheruser', '5678', 'has been warned');
    $dto = DiscordModerationEventDTO::fromDump($raw);

    expect($dto)->not->toBeNull()
        ->and($dto->type)->toBe(ModerationType::Warn);
});

test('DiscordModerationEventDTO extracts ban from heartdevs embed with reason', function (): void {
    $raw = heartdevsModerationLog('367487241171501076', '237242679283548160', 'Banimento', 'descumpriu regras');
    $dto = DiscordModerationEventDTO::fromDump($raw);

    expect($dto)->not->toBeNull()
        ->and($dto->type)->toBe(ModerationType::Ban)
        ->and($dto->botDiscordId)->toBe('123456789')
        ->and($dto->subjectDiscordId)->toBe('367487241171501076')
        ->and($dto->moderatorDiscordId)->toBe('237242679283548160')
        ->and($dto->reason)->toBe('descumpriu regras');
});

test('DiscordModerationEventDTO returns null for non-moderation messages', function (): void {
    $raw = discordMessage();
    $dto = DiscordModerationEventDTO::fromDump($raw);

    expect($dto)->toBeNull();
});

test('DiscordModerationEventDTO returns null for non-bot messages', function (): void {
    $raw = discordMessage(['author' => ['bot' => false]]);
    $dto = DiscordModerationEventDTO::fromDump($raw);

    expect($dto)->toBeNull();
});

test('DiscordModerationEventDTO::toDatabase exposes type and occurred_at without bot identifier', function (): void {
    $raw = heartdevsModerationLog('111', '222', 'Banimento', 'motivo');
    $dto = DiscordModerationEventDTO::fromDump($raw);

    $result = $dto->toDatabase();

    expect($result['type'])->toBe(ModerationType::Ban)
        ->and($result['occurred_at'])->toBeInstanceOf(CarbonInterface::class)
        ->and($result)->not->toHaveKey('source_bot');
});

// ── ImportDiscordMessageAction Tests ─────────────────────────────

test('it creates message linked to existing external identity', function (): void {
    $identity = createTestIdentity('253642464697516033', 'letsch1');

    $action = resolve(ImportDiscordMessageAction::class);
    $message = $action->handle(DiscordMessageDTO::fromDump(discordMessage()));

    expect($message)->toBeInstanceOf(Message::class)
        ->and($message->external_identity_id)->toBe($identity->id)
        ->and($message->content)->toBe('Olá, mundo!');
});

test('it creates user and external identity for unknown author', function (): void {
    $action = resolve(ImportDiscordMessageAction::class);
    $action->handle(DiscordMessageDTO::fromDump(discordMessage()));

    $this->assertDatabaseHas('users', ['username' => 'letsch1']);
    $this->assertDatabaseHas('external_identities', [
        'provider' => 'discord',
        'external_account_id' => '253642464697516033',
    ]);
});

test('it upserts message when provider_message_id already exists', function (): void {
    $action = resolve(ImportDiscordMessageAction::class);
    $action->handle(DiscordMessageDTO::fromDump(discordMessage()));
    $action->handle(DiscordMessageDTO::fromDump(discordMessage(['content' => 'updated'])));

    expect(Message::query()->count())->toBe(1)
        ->and(Message::query()->first()->content)->toBe('updated');
});

test('it sets obtained_experience to zero', function (): void {
    $action = resolve(ImportDiscordMessageAction::class);
    $message = $action->handle(DiscordMessageDTO::fromDump(discordMessage()));

    expect($message->obtained_experience)->toBe(0);
});

test('it stores projected message metadata without redundant fields', function (): void {
    $action = resolve(ImportDiscordMessageAction::class);
    $message = $action->handle(DiscordMessageDTO::fromDump(discordMessage()));

    expect($message->metadata)->toBeArray()
        ->toHaveKey('type')
        ->not->toHaveKey('author')
        ->not->toHaveKey('id')
        ->not->toHaveKey('channel_id')
        ->not->toHaveKey('timestamp')
        ->not->toHaveKey('content')
        ->not->toHaveKey('reactions');
});

test('it preserves empty arrays and null fields in metadata', function (): void {
    $action = resolve(ImportDiscordMessageAction::class);
    $message = $action->handle(DiscordMessageDTO::fromDump(discordMessage()));

    expect($message->metadata)
        ->toHaveKey('mentions', [])
        ->toHaveKey('mention_roles', [])
        ->toHaveKey('attachments', [])
        ->toHaveKey('embeds', [])
        ->toHaveKey('edited_timestamp', value: null);
});

test('it returns null metadata when projection leaves empty object', function (): void {
    $action = resolve(ImportDiscordMessageAction::class);
    $message = $action->handle(DiscordMessageDTO::fromDump([
        'id' => '999999999999',
        'channel_id' => '111111111111',
        'timestamp' => '2019-02-03T04:03:46.777000+00:00',
        'content' => '',
        'author' => ['id' => '253642464697516033', 'username' => 'letsch1'],
        'reactions' => [],
    ]));

    expect($message->metadata)->toBeNull();
});

test('it persists full author raw payload into external_identity metadata', function (): void {
    $action = resolve(ImportDiscordMessageAction::class);
    $action->handle(DiscordMessageDTO::fromDump(discordMessage([
        'author' => [
            'id' => '253642464697516033',
            'username' => 'letsch1',
            'global_name' => 'letsch',
            'avatar' => 'abc123',
            'discriminator' => '0001',
            'public_flags' => 64,
        ],
    ])));

    $identity = ExternalIdentity::query()
        ->where('external_account_id', '253642464697516033')
        ->firstOrFail();

    expect($identity->metadata['author']['avatar'] ?? null)->toBe('abc123')
        ->and($identity->metadata['author']['discriminator'] ?? null)->toBe('0001')
        ->and($identity->metadata['author']['public_flags'] ?? null)->toBe(64)
        ->and($identity->metadata['author']['username'] ?? null)->toBe('letsch1')
        ->and($identity->metadata['username'] ?? null)->toBe('letsch1')
        ->and($identity->metadata['global_name'] ?? null)->toBe('letsch')
        ->and($identity->metadata['avatar'] ?? null)->toBe('abc123');
});

test('message import fills missing canonical metadata without overwriting an existing identity', function (): void {
    $identity = createTestIdentity('253642464697516033', 'letsch1');
    $connectedAt = now()->subHour()->startOfSecond();
    $identity->update([
        'credentials' => ClientAccessManager::make(
            accessToken: Crypt::encrypt('oauth-token'),
        ),
        'connected_at' => $connectedAt,
    ]);
    $ownerId = $identity->model_id;

    resolve(ImportDiscordMessageAction::class)->handle(
        DiscordMessageDTO::fromDump(discordMessage([
            'author' => [
                'id' => '253642464697516033',
                'username' => 'changed-in-message',
                'global_name' => 'Changed in message',
                'avatar' => 'changed-avatar',
            ],
        ])),
    );

    $identity->refresh();

    expect($identity->metadata['user'])->toBe([
        'username' => 'letsch1',
        'discriminator' => '0',
    ])
        ->and($identity->metadata)->toMatchArray([
            'username' => 'letsch1',
            'global_name' => 'Changed in message',
            'avatar' => 'changed-avatar',
        ])
        ->and($identity->credentials->getAccessToken())->toBe('oauth-token')
        ->and($identity->connected_at?->equalTo($connectedAt))->toBeTrue()
        ->and((string) $identity->model_id)->toBe((string) $ownerId);
});

test('message import preserves canonical profile fields and explicit null avatar', function (): void {
    $user = User::factory()->create(['username' => 'profile-user']);
    $connectedAt = now()->subHour()->startOfSecond();
    $identity = ExternalIdentity::factory()->morphFor()->create([
        'model_id' => $user->id,
        'provider' => IdentityProvider::Discord,
        'external_account_id' => '253642464697516033',
        'credentials' => ClientAccessManager::make(
            accessToken: Crypt::encrypt('oauth-token'),
        ),
        'connected_at' => $connectedAt,
        'metadata' => [
            'username' => 'profile-user',
            'global_name' => 'Profile Name',
            'avatar' => null,
            'user' => [
                'username' => 'profile-user',
                'global_name' => 'Profile Name',
                'avatar' => null,
                'public_flags' => 64,
            ],
        ],
    ]);

    resolve(ImportDiscordMessageAction::class)->handle(
        DiscordMessageDTO::fromDump(discordMessage([
            'author' => [
                'id' => '253642464697516033',
                'username' => 'message-user',
                'global_name' => 'Message Name',
                'avatar' => 'message-avatar',
            ],
        ])),
    );

    $identity->refresh();

    expect($identity->metadata)->toMatchArray([
        'username' => 'profile-user',
        'global_name' => 'Profile Name',
        'avatar' => null,
        'user' => [
            'username' => 'profile-user',
            'global_name' => 'Profile Name',
            'avatar' => null,
            'public_flags' => 64,
        ],
    ])
        ->and($identity->metadata['author'])->toMatchArray([
            'username' => 'message-user',
            'global_name' => 'Message Name',
            'avatar' => 'message-avatar',
        ])
        ->and($identity->credentials->getAccessToken())->toBe('oauth-token')
        ->and($identity->connected_at?->equalTo($connectedAt))->toBeTrue()
        ->and((string) $identity->model_id)->toBe((string) $user->id);
});

test('it parses sent_at from discord timestamp', function (): void {
    $action = resolve(ImportDiscordMessageAction::class);
    $message = $action->handle(DiscordMessageDTO::fromDump(discordMessage()));

    expect($message->sent_at)->toBeInstanceOf(CarbonInterface::class)
        ->and($message->sent_at->year)->toBe(2_019);
});

// ── ImportDiscordReactionsAction Tests ───────────────────────────

test('it persists reactions with reactable_type message and reactable_id', function (): void {
    createTestIdentity('253642464697516033', 'letsch1');

    $msgAction = resolve(ImportDiscordMessageAction::class);
    $message = $msgAction->handle(DiscordMessageDTO::fromDump(discordMessage()));

    $reactions = DiscordMessageReactionDTO::fromDumpMessage(discordMessage(['reactions' => [
        ['emoji' => ['id' => null, 'name' => '👍'], 'count' => 3, 'count_details' => ['burst' => 0, 'normal' => 3]],
        ['emoji' => ['id' => '546', 'name' => 'he4rt'], 'count' => 5, 'count_details' => ['burst' => 0, 'normal' => 5]],
    ]]));

    $reactionsAction = resolve(ImportDiscordReactionsAction::class);
    $reactionsAction->handle($message, $reactions);

    expect(Reaction::query()->count())->toBe(2);

    $this->assertDatabaseHas('activity_reactions', [
        'reactable_type' => 'message',
        'reactable_id' => $message->id,
        'emoji_key' => 'u:👍',
        'count' => 3,
    ]);

    $this->assertDatabaseHas('activity_reactions', [
        'reactable_type' => 'message',
        'reactable_id' => $message->id,
        'emoji_key' => '546',
        'emoji_name' => 'he4rt',
    ]);
});

test('it is idempotent - re-running updates counts instead of duplicating', function (): void {
    createTestIdentity('253642464697516033', 'letsch1');

    $msgAction = resolve(ImportDiscordMessageAction::class);
    $message = $msgAction->handle(DiscordMessageDTO::fromDump(discordMessage()));

    $reactionsAction = resolve(ImportDiscordReactionsAction::class);

    $reactions1 = [new DiscordMessageReactionDTO(emojiId: null, emojiName: '👍', count: 3, countBurst: 0, countNormal: 3)];
    $reactionsAction->handle($message, $reactions1);

    $reactions2 = [new DiscordMessageReactionDTO(emojiId: null, emojiName: '👍', count: 7, countBurst: 1, countNormal: 6)];
    $reactionsAction->handle($message, $reactions2);

    expect(Reaction::query()->count())->toBe(1)
        ->and(Reaction::query()->first()->count)->toBe(7);
});

test('it updates message reaction counters', function (): void {
    createTestIdentity('253642464697516033', 'letsch1');

    $msgAction = resolve(ImportDiscordMessageAction::class);
    $message = $msgAction->handle(DiscordMessageDTO::fromDump(discordMessage()));

    $reactions = [
        new DiscordMessageReactionDTO(emojiId: null, emojiName: '👍', count: 3, countBurst: 0, countNormal: 3),
        new DiscordMessageReactionDTO(emojiId: '546', emojiName: 'he4rt', count: 5, countBurst: 0, countNormal: 5),
    ];

    $reactionsAction = resolve(ImportDiscordReactionsAction::class);
    $reactionsAction->handle($message, $reactions);

    $message->refresh();

    expect($message->reactions_count)->toBe(2)
        ->and($message->reactions_total)->toBe(8);
});

test('Message::reactions returns MorphMany relation', function (): void {
    createTestIdentity('253642464697516033', 'letsch1');

    $msgAction = resolve(ImportDiscordMessageAction::class);
    $message = $msgAction->handle(DiscordMessageDTO::fromDump(discordMessage()));

    $reactions = [new DiscordMessageReactionDTO(emojiId: null, emojiName: '🔥', count: 1, countBurst: 0, countNormal: 1)];

    $reactionsAction = resolve(ImportDiscordReactionsAction::class);
    $reactionsAction->handle($message, $reactions);

    expect($message->reactions()->count())->toBe(1)
        ->and($message->reactions()->first()->emoji_name)->toBe('🔥');
});

test('it does nothing when reactions list is empty', function (): void {
    createTestIdentity('253642464697516033', 'letsch1');

    $msgAction = resolve(ImportDiscordMessageAction::class);
    $message = $msgAction->handle(DiscordMessageDTO::fromDump(discordMessage()));

    $reactionsAction = resolve(ImportDiscordReactionsAction::class);
    $reactionsAction->handle($message, []);

    expect(Reaction::query()->count())->toBe(0);
});

// ── ImportDiscordVoiceLogAction Tests ────────────────────────────

test('it creates voice record from dyno join log', function (): void {
    createTestIdentity('529963308577456128', 'voiceuser');

    $dto = DiscordVoiceLogDTO::fromDump(dynoVoiceLog('529963308577456128', '452928009964355604', 'joined'));
    $channelMap = ['452928009964355604' => 'general-voice'];

    $action = resolve(ImportDiscordVoiceLogAction::class);
    $voice = $action->handle($dto, $channelMap);

    expect($voice)->toBeInstanceOf(Voice::class)
        ->and($voice->state)->toBe('joined')
        ->and($voice->channel_name)->toBe('general-voice')
        ->and($voice->obtained_experience)->toBe(0);
});

test('it creates voice record from dyno leave log with left state', function (): void {
    createTestIdentity('529963308577456128', 'voiceuser');

    $dto = DiscordVoiceLogDTO::fromDump(dynoVoiceLog('529963308577456128', '452928009964355604', 'left'));

    $action = resolve(ImportDiscordVoiceLogAction::class);
    $voice = $action->handle($dto, []);

    expect($voice->state)->toBe('left');
});

test('it skips when user has no external identity', function (): void {
    $dto = DiscordVoiceLogDTO::fromDump(dynoVoiceLog('999999999', '452928009964355604'));

    $action = resolve(ImportDiscordVoiceLogAction::class);
    $voice = $action->handle($dto, []);

    expect($voice)->toBeNull();
});

test('it resolves channel name from channel map', function (): void {
    createTestIdentity('529963308577456128', 'voiceuser');

    $dto = DiscordVoiceLogDTO::fromDump(dynoVoiceLog('529963308577456128', '999'));

    $action = resolve(ImportDiscordVoiceLogAction::class);
    $voice = $action->handle($dto, ['999' => 'music-channel']);

    expect($voice->channel_name)->toBe('music-channel');
});

// ── ImportDiscordModerationEventAction Tests ─────────────────────

test('it creates moderation event linked to subject identity (heartdevs)', function (): void {
    $identity = createTestIdentity('367487241171501076', 'punished_user');
    $bot = createTestIdentity('123456789', 'heartdevs_bot');

    $raw = heartdevsModerationLog('367487241171501076', '237242679283548160', 'Banimento', 'motivo');
    $dto = DiscordModerationEventDTO::fromDump($raw);

    $action = resolve(ImportDiscordModerationEventAction::class);
    $event = $action->handle($dto);

    expect($event)->toBeInstanceOf(ModerationEvent::class)
        ->and($event->type)->toBe(ModerationType::Ban)
        ->and($event->external_identity_id)->toBe($identity->id)
        ->and($event->source_identity_id)->toBe($bot->id);
});

test('it creates moderation event with null subject when dyno username does not match', function (): void {
    $raw = dynoModerationLog('unknownuser', '9999', 'was banned');
    $dto = DiscordModerationEventDTO::fromDump($raw);

    $action = resolve(ImportDiscordModerationEventAction::class);
    $event = $action->handle($dto);

    expect($event->external_identity_id)->toBeNull()
        ->and($event->type)->toBe(ModerationType::Ban);
});

test('it stores moderator identity from heartdevs embed', function (): void {
    createTestIdentity('367487241171501076', 'subject');
    $moderator = createTestIdentity('237242679283548160', 'moderator');

    $raw = heartdevsModerationLog('367487241171501076', '237242679283548160', 'Banimento', 'motivo');
    $dto = DiscordModerationEventDTO::fromDump($raw);

    $action = resolve(ImportDiscordModerationEventAction::class);
    $event = $action->handle($dto);

    expect($event->moderator_identity_id)->toBe($moderator->id);
});

test('it stores reason and occurred_at from embed', function (): void {
    $raw = heartdevsModerationLog('111', '222', 'Advertência', 'comportamento inadequado');
    $dto = DiscordModerationEventDTO::fromDump($raw);

    $action = resolve(ImportDiscordModerationEventAction::class);
    $event = $action->handle($dto);

    expect($event->reason)->toBe('comportamento inadequado')
        ->and($event->occurred_at)->toBeInstanceOf(CarbonInterface::class);
});

test('it links source_message_id when provided', function (): void {
    createTestIdentity('253642464697516033', 'letsch1');

    $msgAction = resolve(ImportDiscordMessageAction::class);
    $message = $msgAction->handle(DiscordMessageDTO::fromDump(discordMessage()));

    $raw = heartdevsModerationLog('111', '222', 'Banimento', 'motivo');
    $dto = DiscordModerationEventDTO::fromDump($raw);

    $action = resolve(ImportDiscordModerationEventAction::class);
    $event = $action->handle($dto, $message->id);

    expect($event->source_message_id)->toBe($message->id);
});

test('it is idempotent when re-importing the same source message', function (): void {
    createTestIdentity('253642464697516033', 'letsch1');

    $msgAction = resolve(ImportDiscordMessageAction::class);
    $message = $msgAction->handle(DiscordMessageDTO::fromDump(discordMessage()));

    $raw = heartdevsModerationLog('111', '222', 'Banimento', 'motivo');
    $dto = DiscordModerationEventDTO::fromDump($raw);

    $action = resolve(ImportDiscordModerationEventAction::class);
    $first = $action->handle($dto, $message->id);
    $second = $action->handle($dto, $message->id);

    expect($first->id)->toBe($second->id)
        ->and(ModerationEvent::query()->where('source_message_id', $message->id)->count())->toBe(1);
});

test('it disambiguates user name when author global_name collides', function (): void {
    $action = resolve(ImportDiscordMessageAction::class);

    $action->handle(DiscordMessageDTO::fromDump(discordMessage([
        'id' => '900000000000000001',
        'author' => ['id' => '111', 'username' => 'user_a', 'global_name' => 'Joao', 'bot' => false],
    ])));

    $action->handle(DiscordMessageDTO::fromDump(discordMessage([
        'id' => '900000000000000002',
        'author' => ['id' => '222', 'username' => 'user_b', 'global_name' => 'Joao', 'bot' => false],
    ])));

    expect(User::query()->where('name', 'Joao')->count())->toBe(1)
        ->and(User::query()->where('username', 'user_a')->value('name'))->toBe('Joao')
        ->and(User::query()->where('username', 'user_b')->value('name'))->toBe('user_b');
});

// ── Fase 1: canonical columns populated by adapter ───────────────

test('it populates canonical kind + raw_message_type from adapter', function (): void {
    createTestIdentity('253642464697516033', 'letsch1');

    $raw = discordMessage(['type' => 19, 'message_reference' => ['message_id' => 'parent-msg-1']]);

    $action = resolve(ImportDiscordMessageAction::class);
    $message = $action->handle(DiscordMessageDTO::fromDump($raw));

    expect($message->kind)->toBe(MessageKind::Reply)
        ->and($message->raw_message_type)->toBe(19)
        ->and($message->reply_to_provider_message_id)->toBe('parent-msg-1');
});

test('it populates source_kind based on author flags', function (): void {
    createTestIdentity('253642464697516033', 'letsch1');

    $action = resolve(ImportDiscordMessageAction::class);
    $message = $action->handle(
        DiscordMessageDTO::fromDump(discordMessage(['author' => ['bot' => true]])),
    );

    expect($message->source_kind)->toBe(MessageSourceKind::Bot);
});

test('it populates is_pinned, mentions_everyone and mention_role_count', function (): void {
    createTestIdentity('253642464697516033', 'letsch1');

    $raw = discordMessage([
        'pinned' => true,
        'mention_everyone' => true,
        'mention_roles' => ['r1', 'r2'],
    ]);

    $action = resolve(ImportDiscordMessageAction::class);
    $message = $action->handle(DiscordMessageDTO::fromDump($raw));

    expect($message->is_pinned)->toBeTrue()
        ->and($message->mentions_everyone)->toBeTrue()
        ->and($message->mention_role_count)->toBe(2);
});

test('it parses edited_at into a Carbon instance', function (): void {
    createTestIdentity('253642464697516033', 'letsch1');

    $raw = discordMessage(['edited_timestamp' => '2024-05-01T10:00:00+00:00']);

    $action = resolve(ImportDiscordMessageAction::class);
    $message = $action->handle(DiscordMessageDTO::fromDump($raw));

    expect($message->edited_at)->toBeInstanceOf(CarbonInterface::class)
        ->and($message->edited_at->year)->toBe(2_024);
});

// ── Fase 1: Deleted User coalescence ─────────────────────────────

test('Deleted User DTO namespaces authorUsername by Discord ID', function (): void {
    $raw = discordMessage(['author' => ['id' => '500', 'username' => 'Deleted User']]);
    $dto = DiscordMessageDTO::fromDump($raw);

    expect($dto->authorUsername)->toBe('deleted_user_500');
});

test('it keeps deleted Discord users as separate rows', function (): void {
    $action = resolve(ImportDiscordMessageAction::class);

    $action->handle(DiscordMessageDTO::fromDump(discordMessage([
        'id' => '800000000000000001',
        'author' => ['id' => '111', 'username' => 'Deleted User', 'global_name' => null, 'bot' => false],
    ])));

    $action->handle(DiscordMessageDTO::fromDump(discordMessage([
        'id' => '800000000000000002',
        'author' => ['id' => '222', 'username' => 'Deleted User', 'global_name' => null, 'bot' => false],
    ])));

    expect(User::query()->where('username', 'deleted_user_111')->count())->toBe(1)
        ->and(User::query()->where('username', 'deleted_user_222')->count())->toBe(1)
        ->and(ExternalIdentity::query()->where('external_account_id', '111')->count())->toBe(1)
        ->and(ExternalIdentity::query()->where('external_account_id', '222')->count())->toBe(1);
});

// ── Fase 1: Voice upsert idempotency ─────────────────────────────

test('it upserts voice events by provider_message_id', function (): void {
    createTestIdentity('529963308577456128', 'voiceuser');

    $raw = dynoVoiceLog('529963308577456128', '452928009964355604', 'joined');
    $dto = DiscordVoiceLogDTO::fromDump($raw);

    $action = resolve(ImportDiscordVoiceLogAction::class);
    $action->handle($dto, []);
    $action->handle($dto, []);

    expect(Voice::query()->count())->toBe(1)
        ->and(Voice::query()->first()->provider_message_id)->toBe($raw['id']);
});

test('it preserves the Dyno log timestamp in occurred_at', function (): void {
    createTestIdentity('529963308577456128', 'voiceuser');

    $raw = dynoVoiceLog('529963308577456128', '452928009964355604', 'joined');
    $dto = DiscordVoiceLogDTO::fromDump($raw);

    $action = resolve(ImportDiscordVoiceLogAction::class);
    $voice = $action->handle($dto, []);

    expect($voice->occurred_at)->toBeInstanceOf(CarbonInterface::class)
        ->and($voice->occurred_at->toISOString())->toStartWith('2019-02-03');
});

// ── Fase 1: Moderation idempotency + subject metadata ────────────

test('moderation DTO stores parsed subject_username/discriminator in metadata', function (): void {
    $raw = dynoModerationLog('someuser', '1234', 'was banned');
    $dto = DiscordModerationEventDTO::fromDump($raw);

    $result = $dto->toDatabase();

    expect($result['metadata']['subject_username'])->toBe('someuser')
        ->and($result['metadata']['subject_discriminator'])->toBe('1234');
});

test('it upserts moderation events by provider_message_id', function (): void {
    $raw = dynoModerationLog('someuser', '1234', 'was banned');
    $dto = DiscordModerationEventDTO::fromDump($raw);

    $action = resolve(ImportDiscordModerationEventAction::class);
    $first = $action->handle($dto);
    $second = $action->handle($dto);

    expect($first->id)->toBe($second->id)
        ->and(ModerationEvent::query()->count())->toBe(1);
});

// ── Fase 2: mentions / threads / resolved replies ────────────────

test('it populates message_mentions with provider id, position and linked identity when known', function (): void {
    createTestIdentity('253642464697516033', 'letsch1');
    $bob = createTestIdentity('999', 'bob');

    $raw = discordMessage([
        'id' => 'msg-with-mentions',
        'mentions' => [
            ['id' => '999', 'username' => 'bob'],
            ['id' => '888', 'username' => 'ghost'],
        ],
    ]);

    $action = resolve(ImportDiscordMessageAction::class);
    $message = $action->handle(DiscordMessageDTO::fromDump($raw));

    $mentions = MessageMention::query()
        ->where('message_id', $message->id)
        ->orderBy('position')
        ->get();

    expect($mentions)->toHaveCount(2)
        ->and($mentions[0]->mentioned_provider_account_id)->toBe('999')
        ->and($mentions[0]->mentioned_identity_id)->toBe($bob->id)
        ->and($mentions[0]->mentioned_username)->toBe('bob')
        ->and($mentions[0]->position)->toBe(0)
        ->and($mentions[1]->mentioned_provider_account_id)->toBe('888')
        ->and($mentions[1]->mentioned_identity_id)->toBeNull()
        ->and($mentions[1]->position)->toBe(1);
});

test('it upserts mentions idempotently on re-import', function (): void {
    createTestIdentity('253642464697516033', 'letsch1');

    $raw = discordMessage([
        'id' => 'msg-dup-mentions',
        'mentions' => [['id' => '1', 'username' => 'alice']],
    ]);

    $action = resolve(ImportDiscordMessageAction::class);
    $action->handle(DiscordMessageDTO::fromDump($raw));
    $action->handle(DiscordMessageDTO::fromDump($raw));

    expect(MessageMention::query()->count())->toBe(1);
});

test('it creates a message_thread when payload has a thread block', function (): void {
    createTestIdentity('253642464697516033', 'letsch1');

    $raw = discordMessage([
        'id' => 'msg-with-thread',
        'thread' => [
            'id' => 'thread-xyz',
            'name' => 'Conversa',
            'thread_metadata' => ['archived' => false, 'auto_archive_duration' => 1_440],
        ],
    ]);

    $action = resolve(ImportDiscordMessageAction::class);
    $message = $action->handle(DiscordMessageDTO::fromDump($raw));

    $thread = MessageThread::query()->where('message_id', $message->id)->first();

    expect($thread)->not->toBeNull()
        ->and($thread->provider_thread_id)->toBe('thread-xyz')
        ->and($thread->name)->toBe('Conversa')
        ->and($thread->archived)->toBeFalse()
        ->and($thread->auto_archive_duration)->toBe(1_440);
});

test('it resolves reply_to_message_id when parent message already exists', function (): void {
    createTestIdentity('253642464697516033', 'letsch1');

    $action = resolve(ImportDiscordMessageAction::class);
    $parent = $action->handle(
        DiscordMessageDTO::fromDump(discordMessage(['id' => 'parent-1'])),
    );

    $child = $action->handle(
        DiscordMessageDTO::fromDump(discordMessage([
            'id' => 'child-1',
            'type' => 19,
            'message_reference' => ['message_id' => 'parent-1'],
        ])),
    );

    expect($child->reply_to_provider_message_id)->toBe('parent-1')
        ->and($child->reply_to_message_id)->toBe($parent->id);
});

test('it leaves reply_to_message_id null when parent is out of order', function (): void {
    createTestIdentity('253642464697516033', 'letsch1');

    $action = resolve(ImportDiscordMessageAction::class);
    $child = $action->handle(
        DiscordMessageDTO::fromDump(discordMessage([
            'id' => 'child-2',
            'type' => 19,
            'message_reference' => ['message_id' => 'unseen-parent'],
        ])),
    );

    expect($child->reply_to_provider_message_id)->toBe('unseen-parent')
        ->and($child->reply_to_message_id)->toBeNull();
});

// ── Fase 3: attachments / embeds / membership events ─────────────

test('it persists attachments with content type, size and dimensions', function (): void {
    createTestIdentity('253642464697516033', 'letsch1');

    $raw = discordMessage([
        'id' => 'msg-atts',
        'attachments' => [[
            'id' => 'att-1',
            'url' => 'https://cdn.discordapp.com/attachments/foo.png',
            'filename' => 'foo.png',
            'content_type' => 'image/png',
            'size' => 4_096,
            'width' => 800,
            'height' => 600,
        ]],
    ]);

    $action = resolve(ImportDiscordMessageAction::class);
    $message = $action->handle(DiscordMessageDTO::fromDump($raw));

    $attachment = MessageAttachment::query()->where('message_id', $message->id)->first();

    expect($attachment)->not->toBeNull()
        ->and($attachment->filename)->toBe('foo.png')
        ->and($attachment->content_type)->toBe('image/png')
        ->and($attachment->size)->toBe(4_096)
        ->and($attachment->width)->toBe(800);
});

test('attachments are replaced on re-import (no duplicates)', function (): void {
    createTestIdentity('253642464697516033', 'letsch1');

    $raw = discordMessage([
        'id' => 'msg-atts-dup',
        'attachments' => [
            ['id' => 'a', 'url' => 'https://x/a.png', 'filename' => 'a.png'],
            ['id' => 'b', 'url' => 'https://x/b.png', 'filename' => 'b.png'],
        ],
    ]);

    $action = resolve(ImportDiscordMessageAction::class);
    $action->handle(DiscordMessageDTO::fromDump($raw));
    $action->handle(DiscordMessageDTO::fromDump($raw));

    expect(MessageAttachment::query()->count())->toBe(2);
});

test('it persists embeds with derived source_domain and raw payload', function (): void {
    createTestIdentity('253642464697516033', 'letsch1');

    $raw = discordMessage([
        'id' => 'msg-embed',
        'embeds' => [[
            'url' => 'https://github.com/laravel/laravel',
            'title' => 'laravel/laravel',
            'description' => 'The Laravel framework',
            'type' => 'link',
            'thumbnail' => ['url' => 'https://avatars.githubusercontent.com/u/1.png'],
        ]],
    ]);

    $action = resolve(ImportDiscordMessageAction::class);
    $message = $action->handle(DiscordMessageDTO::fromDump($raw));

    $embed = MessageEmbed::query()->where('message_id', $message->id)->first();

    expect($embed)->not->toBeNull()
        ->and($embed->source_domain)->toBe('github.com')
        ->and($embed->kind)->toBe('link')
        ->and($embed->raw['title'])->toBe('laravel/laravel');
});

test('it derives a membership_event for type=7 user joins', function (): void {
    createTestIdentity('253642464697516033', 'letsch1');

    $raw = discordMessage([
        'id' => 'join-1',
        'type' => 7,
        'timestamp' => '2024-03-01T12:00:00+00:00',
    ]);

    $action = resolve(ImportDiscordMessageAction::class);
    $message = $action->handle(DiscordMessageDTO::fromDump($raw));

    $event = MembershipEvent::query()
        ->where('provider_message_id', $message->provider_message_id)
        ->first();

    expect($event)->not->toBeNull()
        ->and($event->kind)->toBe('user_join')
        ->and($event->external_identity_id)->toBe($message->external_identity_id);
});

test('it derives a boost_tier_2 membership_event for type=10', function (): void {
    createTestIdentity('253642464697516033', 'letsch1');

    $raw = discordMessage([
        'id' => 'boost-t2',
        'type' => 10,
        'timestamp' => '2024-03-01T12:00:00+00:00',
    ]);

    $action = resolve(ImportDiscordMessageAction::class);
    $action->handle(DiscordMessageDTO::fromDump($raw));

    expect(MembershipEvent::query()->where('kind', 'boost_tier_2')->count())->toBe(1);
});

test('membership_events are idempotent on re-import', function (): void {
    createTestIdentity('253642464697516033', 'letsch1');

    $raw = discordMessage([
        'id' => 'join-dup',
        'type' => 7,
        'timestamp' => '2024-03-01T12:00:00+00:00',
    ]);

    $action = resolve(ImportDiscordMessageAction::class);
    $action->handle(DiscordMessageDTO::fromDump($raw));
    $action->handle(DiscordMessageDTO::fromDump($raw));

    expect(MembershipEvent::query()->count())->toBe(1);
});

test('resolveOrCreateUser reuses existing legacy #0 user when no discord identity exists', function (): void {
    $existing = User::factory()->create(['username' => 'oldbie#0', 'name' => 'Oldbie']);

    $raw = discordMessage([
        'id' => 'msg-legacy',
        'author' => ['id' => '999000', 'username' => 'oldbie', 'global_name' => 'Oldbie'],
    ]);

    $action = resolve(ImportDiscordMessageAction::class);
    $message = $action->handle(DiscordMessageDTO::fromDump($raw));

    $identity = ExternalIdentity::query()
        ->where('provider', IdentityProvider::Discord)
        ->where('external_account_id', '999000')
        ->first();

    expect((string) $identity->model_id)->toBe((string) $existing->id)
        ->and((string) $message->external_identity_id)->toBe((string) $identity->id)
        ->and(User::query()->where('username', 'like', 'oldbie%')->count())->toBe(1);
});
