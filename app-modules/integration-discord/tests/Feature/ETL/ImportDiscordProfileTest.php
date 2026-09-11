<?php

declare(strict_types=1);

use He4rt\Identity\Auth\Actions\AttachProviderToUser;
use He4rt\Identity\ExternalIdentity\Data\ClientAccessManager;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use He4rt\IntegrationDiscord\ETL\Actions\ImportDiscordMessageAction;
use He4rt\IntegrationDiscord\ETL\Actions\ImportDiscordProfileAction;
use He4rt\IntegrationDiscord\ETL\DTOs\ConnectedAccountDTO;
use He4rt\IntegrationDiscord\ETL\DTOs\DiscordMessageDTO;
use He4rt\IntegrationDiscord\ETL\DTOs\DiscordProfileDTO;
use He4rt\IntegrationDiscord\OAuth\DiscordOAuthAccessDTO;
use He4rt\IntegrationDiscord\OAuth\DiscordOAuthUser;
use Illuminate\Support\Facades\Crypt;

/**
 * @param  array<string, mixed>  $overrides
 */
function discordProfile(array $overrides = []): array
{
    $default = [
        'user' => [
            'id' => '49615312957476864',
            'username' => '_tats',
            'global_name' => 'Madruguinha',
            'avatar' => '7638814994b449231c5ff17dd84500bd',
            'avatar_decoration_data' => null,
            'collectibles' => null,
            'discriminator' => '0',
            'display_name_styles' => null,
            'public_flags' => 0,
            'primary_guild' => null,
            'clan' => null,
            'flags' => 0,
            'banner' => null,
            'banner_color' => null,
            'accent_color' => null,
            'bio' => '',
        ],
        'connected_accounts' => [
            ['type' => 'twitch', 'id' => '81085454', 'name' => 'fewerygor', 'verified' => true],
        ],
        'premium_type' => 0,
        'premium_since' => null,
        'premium_guild_since' => null,
        'profile_themes_experiment_bucket' => 4,
        'user_profile' => [
            'bio' => '',
            'accent_color' => null,
            'pronouns' => '',
            'profile_effect' => null,
            'collectibles' => [],
        ],
        'badges' => [
            ['id' => 'legacy_username', 'description' => 'Originally known as Tats#0927', 'icon' => '6de6d34650760ba5551a79732e98ed60'],
        ],
        'guild_badges' => [],
        'widgets' => [],
        'guild_member' => [
            'avatar' => null,
            'banner' => null,
            'communication_disabled_until' => null,
            'flags' => 10,
            'joined_at' => '2024-02-29T02:34:52.077000+00:00',
            'nick' => null,
            'pending' => false,
            'premium_since' => null,
            'roles' => [],
            'unusual_dm_activity_until' => null,
            'collectibles' => null,
            'display_name_styles' => null,
            'bio' => '',
            'mute' => false,
            'deaf' => false,
        ],
        'guild_member_profile' => [
            'guild_id' => '452926217558163456',
            'pronouns' => '',
            'profile_effect' => null,
            'collectibles' => [],
        ],
        'legacy_username' => 'Tats#0927',
    ];

    if (array_key_exists('connected_accounts', $overrides) && $overrides['connected_accounts'] === []) {
        $default['connected_accounts'] = [];
        unset($overrides['connected_accounts']);
    }

    return array_replace_recursive($default, $overrides);
}

// DTO Tests

test('it creates DTO from discord dump format', function (): void {
    $profile = discordProfile();

    $dto = DiscordProfileDTO::fromDump($profile);

    expect($dto->discordId)->toBe('49615312957476864')
        ->and($dto->username)->toBe('_tats')
        ->and($dto->name)->toBe('Madruguinha')
        ->and($dto->joinedAt)->toBe('2024-02-29T02:34:52.077000+00:00');
});

test('it falls back to username when global_name is null', function (): void {
    $profile = discordProfile(['user' => ['global_name' => null]]);

    $dto = DiscordProfileDTO::fromDump($profile);

    expect($dto->name)->toBe('_tats');
});

test('it preserves entire dump in metadata', function (): void {
    $profile = discordProfile();

    $dto = DiscordProfileDTO::fromDump($profile);

    expect($dto->metadata)->toBe($profile)
        ->and($dto->metadata)->toHaveKeys([
            'user',
            'connected_accounts',
            'premium_type',
            'premium_since',
            'premium_guild_since',
            'profile_themes_experiment_bucket',
            'user_profile',
            'badges',
            'guild_badges',
            'widgets',
            'guild_member',
            'guild_member_profile',
            'legacy_username',
        ]);
});

// Action Tests

test('it creates user and external identity from discord profile', function (): void {
    $profile = discordProfile();
    $dto = DiscordProfileDTO::fromDump($profile);

    $action = resolve(ImportDiscordProfileAction::class);
    $identity = $action->handle($dto);

    expect($identity)->toBeInstanceOf(ExternalIdentity::class);

    $this->assertDatabaseHas('users', [
        'username' => '_tats',
        'name' => 'Madruguinha',
    ]);

    $this->assertDatabaseHas('external_identities', [
        'provider' => 'discord',
        'external_account_id' => '49615312957476864',
    ]);
});

test('it upserts external identity when already exists', function (): void {
    $action = resolve(ImportDiscordProfileAction::class);

    $profileOld = discordProfile(['user_profile' => ['bio' => 'old bio'], 'connected_accounts' => []]);
    $action->handle(DiscordProfileDTO::fromDump($profileOld));

    $profileNew = discordProfile(['user_profile' => ['bio' => 'new bio'], 'connected_accounts' => []]);
    $identity = $action->handle(DiscordProfileDTO::fromDump($profileNew));

    expect(ExternalIdentity::query()->count())->toBe(1)
        ->and(User::query()->where('username', '_tats')->count())->toBe(1)
        ->and($identity->metadata['user_profile']['bio'])->toBe('new bio');
});

test('it stores complete discord metadata in external identity', function (): void {
    $profile = discordProfile();
    $dto = DiscordProfileDTO::fromDump($profile);

    $action = resolve(ImportDiscordProfileAction::class);
    $identity = $action->handle($dto);

    $identity->refresh();

    $metadata = $identity->metadata;

    expect($metadata)->toHaveKeys([
        'user',
        'connected_accounts',
        'badges',
        'guild_member',
        'user_profile',
        'premium_type',
        'legacy_username',
        'guild_member_profile',
    ])
        ->and($metadata['connected_accounts'])->toHaveCount(1)
        ->and($metadata['connected_accounts'][0]['type'])->toBe('twitch')
        ->and($metadata['badges'][0]['id'])->toBe('legacy_username')
        ->and($metadata['legacy_username'])->toBe('Tats#0927');
});

test('it does not treat a guild join as an authenticated connection', function (): void {
    $profile = discordProfile();
    $dto = DiscordProfileDTO::fromDump($profile);

    $action = resolve(ImportDiscordProfileAction::class);
    $identity = $action->handle($dto);

    expect($dto->joinedAt)->not->toBeNull()
        ->and($identity->connected_at)->toBeNull()
        ->and($identity->credentials->getAccessToken())->toBeNull();
});

test('profile import preserves oauth credentials connection state and owner', function (): void {
    $user = User::factory()->create(['username' => '_tats']);
    $connectedBy = User::factory()->create();
    $connectedAt = now()->subDay()->startOfSecond();

    $existing = ExternalIdentity::factory()->morphFor()->create([
        'model_id' => $user->id,
        'provider' => IdentityProvider::Discord,
        'external_account_id' => '49615312957476864',
        'credentials' => ClientAccessManager::make(
            accessToken: Crypt::encrypt('oauth-token'),
            refreshToken: Crypt::encrypt('oauth-refresh'),
        ),
        'connected_by' => $connectedBy->id,
        'connected_at' => $connectedAt,
        'metadata' => [
            'email' => 'discord@example.com',
            'username' => '_tats',
            'avatar' => 'https://cdn.discordapp.com/avatars/49615312957476864/oauth.png',
        ],
    ]);

    $identity = resolve(ImportDiscordProfileAction::class)->handle(
        DiscordProfileDTO::fromDump(discordProfile([
            'user' => ['email' => 'profile-must-not-replace@example.com'],
            'connected_accounts' => [],
        ])),
    );

    expect($identity->id)->toBe($existing->id)
        ->and((string) $identity->model_id)->toBe((string) $user->id)
        ->and((string) $identity->connected_by)->toBe((string) $connectedBy->id)
        ->and($identity->connected_at?->equalTo($connectedAt))->toBeTrue()
        ->and($identity->credentials->getAccessToken())->toBe('oauth-token')
        ->and($identity->credentials->getRefreshToken())->toBe('oauth-refresh')
        ->and($identity->metadata)->toMatchArray([
            'email' => 'discord@example.com',
            'username' => '_tats',
            'global_name' => 'Madruguinha',
            'avatar' => '7638814994b449231c5ff17dd84500bd',
        ])
        ->and($identity->metadata)->toHaveKeys(['user', 'badges', 'guild_member']);
});

test('profile import preserves an avatar when the snapshot omits it and clears it when explicitly null', function (): void {
    $user = User::factory()->create(['username' => '_tats']);
    ExternalIdentity::factory()->morphFor()->create([
        'model_id' => $user->id,
        'provider' => IdentityProvider::Discord,
        'external_account_id' => '49615312957476864',
        'metadata' => [
            'username' => '_tats',
            'avatar' => 'known-avatar',
        ],
    ]);

    $profileWithoutAvatar = discordProfile(['connected_accounts' => []]);
    unset($profileWithoutAvatar['user']['avatar']);

    $identity = resolve(ImportDiscordProfileAction::class)->handle(
        DiscordProfileDTO::fromDump($profileWithoutAvatar),
    );

    expect($identity->metadata['avatar'])->toBe('known-avatar');

    $identity = resolve(ImportDiscordProfileAction::class)->handle(
        DiscordProfileDTO::fromDump(discordProfile([
            'user' => ['avatar' => null],
            'connected_accounts' => [],
        ])),
    );

    expect($identity->metadata)->toHaveKey('avatar', value: null);
});

test('oauth connection preserves profile snapshot and fills canonical metadata', function (): void {
    $profileIdentity = resolve(ImportDiscordProfileAction::class)->handle(
        DiscordProfileDTO::fromDump(discordProfile(['connected_accounts' => []])),
    );

    $access = DiscordOAuthAccessDTO::make([
        'access_token' => 'new-access-token',
        'refresh_token' => 'new-refresh-token',
        'expires_in' => 3_600,
    ]);
    $oauthUser = DiscordOAuthUser::make($access, [
        'id' => '49615312957476864',
        'username' => '_tats_oauth',
        'global_name' => 'Madruguinha OAuth',
        'email' => 'discord@example.com',
        'avatar' => 'oauth-avatar',
    ]);

    $identity = resolve(AttachProviderToUser::class)->execute(
        $profileIdentity->user,
        $oauthUser,
        $access,
    );

    expect($identity->id)->toBe($profileIdentity->id)
        ->and($identity->credentials->getAccessToken())->toBe('new-access-token')
        ->and($identity->connected_at)->not->toBeNull()
        ->and($identity->metadata)->toMatchArray([
            'email' => 'discord@example.com',
            'username' => '_tats_oauth',
            'global_name' => 'Madruguinha OAuth',
            'avatar' => 'https://cdn.discordapp.com/avatars/49615312957476864/oauth-avatar.png',
        ])
        ->and($identity->metadata)->toHaveKeys(['user', 'badges', 'guild_member']);
});

test('oauth connection clears a profile avatar only when Discord explicitly returns null', function (): void {
    $profileIdentity = resolve(ImportDiscordProfileAction::class)->handle(
        DiscordProfileDTO::fromDump(discordProfile(['connected_accounts' => []])),
    );

    $access = DiscordOAuthAccessDTO::make([
        'access_token' => 'new-access-token',
        'refresh_token' => 'new-refresh-token',
        'expires_in' => 3_600,
    ]);
    $oauthUser = DiscordOAuthUser::make($access, [
        'id' => '49615312957476864',
        'username' => '_tats',
        'global_name' => 'Madruguinha',
        'email' => 'discord@example.com',
        'avatar' => null,
    ]);

    $identity = resolve(AttachProviderToUser::class)->execute(
        $profileIdentity->user,
        $oauthUser,
        $access,
    );

    expect($identity->metadata)->toHaveKey('avatar', value: null)
        ->and($identity->metadata['user']['avatar'])->toBe('7638814994b449231c5ff17dd84500bd')
        ->and($identity->credentials->getAccessToken())->toBe('new-access-token');
});

test('profile import enriches an identity created from a message without replacing its owner', function (): void {
    resolve(ImportDiscordMessageAction::class)->handle(
        DiscordMessageDTO::fromDump([
            'id' => 'message-1',
            'channel_id' => 'channel-1',
            'timestamp' => '2026-08-25T10:00:00.000000+00:00',
            'content' => 'hello',
            'author' => [
                'id' => '49615312957476864',
                'username' => '_tats',
                'global_name' => 'Old display name',
                'avatar' => 'old-avatar',
            ],
        ]),
    );

    $messageIdentity = ExternalIdentity::query()
        ->where('provider', IdentityProvider::Discord)
        ->where('external_account_id', '49615312957476864')
        ->firstOrFail();
    $ownerId = $messageIdentity->model_id;

    $identity = resolve(ImportDiscordProfileAction::class)->handle(
        DiscordProfileDTO::fromDump(discordProfile(['connected_accounts' => []])),
    );

    expect($identity->id)->toBe($messageIdentity->id)
        ->and((string) $identity->model_id)->toBe((string) $ownerId)
        ->and($identity->metadata['author'])->toMatchArray([
            'username' => '_tats',
            'global_name' => 'Old display name',
            'avatar' => 'old-avatar',
        ])
        ->and($identity->metadata)->toMatchArray([
            'username' => '_tats',
            'global_name' => 'Madruguinha',
            'avatar' => '7638814994b449231c5ff17dd84500bd',
        ])
        ->and($identity->metadata)->toHaveKeys(['user', 'badges', 'guild_member']);
});

test('profile import preserves an authenticated connected account', function (): void {
    $user = User::factory()->create(['username' => '_tats']);
    ExternalIdentity::factory()->morphFor()->create([
        'model_id' => $user->id,
        'provider' => IdentityProvider::Discord,
        'external_account_id' => '49615312957476864',
    ]);

    $connectedAt = now()->subHour()->startOfSecond();
    $twitch = ExternalIdentity::factory()->morphFor()->create([
        'model_id' => $user->id,
        'provider' => IdentityProvider::Twitch,
        'external_account_id' => '81085454',
        'credentials' => ClientAccessManager::make(
            accessToken: Crypt::encrypt('twitch-token'),
        ),
        'connected_at' => $connectedAt,
        'metadata' => [
            'email' => 'twitch@example.com',
            'username' => 'oauth-name',
        ],
    ]);

    resolve(ImportDiscordProfileAction::class)->handle(
        DiscordProfileDTO::fromDump(discordProfile()),
    );

    $twitch->refresh();

    expect((string) $twitch->model_id)->toBe((string) $user->id)
        ->and($twitch->credentials->getAccessToken())->toBe('twitch-token')
        ->and($twitch->connected_at?->equalTo($connectedAt))->toBeTrue()
        ->and($twitch->metadata)->toMatchArray([
            'email' => 'twitch@example.com',
            'username' => 'oauth-name',
            'name' => 'fewerygor',
            'verified' => true,
        ]);
});

test('it creates external identities for each connected account', function (): void {
    $profile = discordProfile([
        'connected_accounts' => [
            ['type' => 'twitch', 'id' => '81085454', 'name' => 'fewerygor', 'verified' => true],
            ['type' => 'github', 'id' => '123456', 'name' => 'he4rtdevs', 'verified' => true],
        ],
    ]);
    $dto = DiscordProfileDTO::fromDump($profile);

    $action = resolve(ImportDiscordProfileAction::class);
    $action->handle($dto);

    expect(ExternalIdentity::query()->count())->toBe(3)
        ->and(ExternalIdentity::query()->where('provider', 'discord')->exists())->toBeTrue()
        ->and(ExternalIdentity::query()->where('provider', 'twitch')->exists())->toBeTrue()
        ->and(ExternalIdentity::query()->where('provider', 'github')->exists())->toBeTrue();

    $user = User::query()->where('username', '_tats')->first();
    expect(ExternalIdentity::query()->where('model_id', $user->id)->count())->toBe(3);
});

test('it stores connected account metadata in external identity', function (): void {
    $profile = discordProfile([
        'connected_accounts' => [
            [
                'type' => 'steam',
                'id' => '123456789',
                'name' => 'he4rt_player',
                'verified' => true,
                'metadata' => ['game_count' => 42],
            ],
        ],
    ]);
    $dto = DiscordProfileDTO::fromDump($profile);

    $action = resolve(ImportDiscordProfileAction::class);
    $action->handle($dto);

    $steamIdentity = ExternalIdentity::query()->where('provider', 'steam')->first();

    expect($steamIdentity->metadata)->toHaveKeys(['type', 'id', 'name', 'verified', 'metadata'])
        ->and($steamIdentity->metadata['type'])->toBe('steam')
        ->and($steamIdentity->metadata['id'])->toBe('123456789')
        ->and($steamIdentity->metadata['name'])->toBe('he4rt_player')
        ->and($steamIdentity->metadata['metadata']['game_count'])->toBe(42);
});

test('it handles profile with no connected accounts', function (): void {
    $profile = discordProfile(['connected_accounts' => []]);
    $dto = DiscordProfileDTO::fromDump($profile);

    $action = resolve(ImportDiscordProfileAction::class);
    $action->handle($dto);

    expect(ExternalIdentity::query()->count())->toBe(1)
        ->and(ExternalIdentity::query()->where('provider', 'discord')->exists())->toBeTrue();
});

test('it upserts connected account external identities', function (): void {
    $action = resolve(ImportDiscordProfileAction::class);

    $profileOld = discordProfile([
        'connected_accounts' => [
            ['type' => 'spotify', 'id' => 'user123', 'name' => 'old_name', 'verified' => false],
        ],
    ]);
    $action->handle(DiscordProfileDTO::fromDump($profileOld));

    expect(ExternalIdentity::query()->count())->toBe(2)
        ->and(ExternalIdentity::query()->where('provider', 'spotify')->first()->metadata['name'])->toBe('old_name');

    $profileNew = discordProfile([
        'connected_accounts' => [
            ['type' => 'spotify', 'id' => 'user123', 'name' => 'new_name', 'verified' => true],
        ],
    ]);
    $action->handle(DiscordProfileDTO::fromDump($profileNew));

    expect(ExternalIdentity::query()->count())->toBe(2)
        ->and(ExternalIdentity::query()->where('provider', 'spotify')->count())->toBe(1)
        ->and(ExternalIdentity::query()->where('provider', 'spotify')->first()->metadata['name'])->toBe('new_name');
});

test('connected account dto creates from dump format', function (): void {
    $account = [
        'type' => 'github',
        'id' => '123456',
        'name' => 'he4rtdevs',
        'verified' => true,
        'metadata' => ['extra' => 'data'],
    ];

    $dto = ConnectedAccountDTO::fromDump($account);

    expect($dto->provider)->toBe(IdentityProvider::GitHub)
        ->and($dto->externalAccountId)->toBe('123456')
        ->and($dto->name)->toBe('he4rtdevs')
        ->and($dto->verified)->toBeTrue()
        ->and($dto->metadata)->toBe($account);
});

// Regression: identity-first lookup prevents user duplication on Discord username changes.

test('it reuses existing user when discord identity already exists with legacy #0 username', function (): void {
    $action = resolve(ImportDiscordProfileAction::class);

    $oldUser = User::factory()->create(['username' => 'sun.dev_#0', 'name' => 'Sun']);
    ExternalIdentity::factory()
        ->morphFor()
        ->create([
            'provider' => IdentityProvider::Discord,
            'external_account_id' => '49615312957476864',
            'model_id' => $oldUser->id,
        ]);

    $action->handle(
        DiscordProfileDTO::fromDump(discordProfile([
            'user' => ['username' => 'sun.dev_', 'global_name' => 'Sun'],
            'connected_accounts' => [],
        ])),
    );

    expect(User::query()->whereIn('username', ['sun.dev_', 'sun.dev_#0'])->count())->toBe(1)
        ->and(User::query()->find($oldUser->id)->username)->toBe('sun.dev_');

    $identity = ExternalIdentity::query()
        ->where('provider', IdentityProvider::Discord)
        ->where('external_account_id', '49615312957476864')
        ->first();

    expect((string) $identity->model_id)->toBe((string) $oldUser->id);
});

test('it updates username when discord pomelo handle changes', function (): void {
    $action = resolve(ImportDiscordProfileAction::class);

    $user = User::factory()->create(['username' => 'oldhandle', 'name' => 'oldhandle']);
    ExternalIdentity::factory()
        ->morphFor()
        ->create([
            'provider' => IdentityProvider::Discord,
            'external_account_id' => '49615312957476864',
            'model_id' => $user->id,
        ]);

    $action->handle(
        DiscordProfileDTO::fromDump(discordProfile([
            'user' => ['username' => 'newhandle', 'global_name' => 'New Display'],
            'connected_accounts' => [],
        ])),
    );

    $user->refresh();
    expect($user->username)->toBe('newhandle')
        ->and($user->name)->toBe('New Display')
        ->and(User::query()->where('username', 'oldhandle')->exists())->toBeFalse();
});

test('it does not steal connected account identity already linked to another user', function (): void {
    $action = resolve(ImportDiscordProfileAction::class);

    $alice = User::factory()->create(['username' => 'alice']);
    $aliceTwitch = ExternalIdentity::factory()
        ->morphFor()
        ->create([
            'provider' => IdentityProvider::Twitch,
            'external_account_id' => '81085454',
            'model_id' => $alice->id,
        ]);

    $action->handle(
        DiscordProfileDTO::fromDump(discordProfile([
            'user' => ['id' => '999', 'username' => 'bob'],
            'connected_accounts' => [
                ['type' => 'twitch', 'id' => '81085454', 'name' => 'fake', 'verified' => true],
            ],
        ])),
    );

    $aliceTwitch->refresh();
    expect((string) $aliceTwitch->model_id)->toBe((string) $alice->id)
        ->and(ExternalIdentity::query()->where('provider', IdentityProvider::Twitch)->count())->toBe(1);
});

test('it creates user when no identity and no username match exists', function (): void {
    $action = resolve(ImportDiscordProfileAction::class);

    $usersBefore = User::query()->count();

    $action->handle(
        DiscordProfileDTO::fromDump(discordProfile([
            'user' => ['id' => '777', 'username' => 'fresh', 'global_name' => 'Fresh'],
            'connected_accounts' => [],
        ])),
    );

    expect(User::query()->count())->toBe($usersBefore + 1)
        ->and(User::query()->where('username', 'fresh')->exists())->toBeTrue();
});

test('it links discord identity to portal user matching username when no identity exists yet', function (): void {
    $action = resolve(ImportDiscordProfileAction::class);

    $portalUser = User::factory()->create(['username' => 'portal_user', 'name' => 'Portal']);

    $action->handle(
        DiscordProfileDTO::fromDump(discordProfile([
            'user' => ['id' => '888', 'username' => 'portal_user'],
            'connected_accounts' => [],
        ])),
    );

    $identity = ExternalIdentity::query()
        ->where('provider', IdentityProvider::Discord)
        ->where('external_account_id', '888')
        ->first();

    expect((string) $identity->model_id)->toBe((string) $portalUser->id);
});
