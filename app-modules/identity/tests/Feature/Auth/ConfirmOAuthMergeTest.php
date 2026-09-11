<?php

declare(strict_types=1);

use He4rt\Identity\Auth\Actions\ConfirmOAuthMerge;
use He4rt\Identity\Auth\Actions\ResolvePendingOAuthMerge;
use He4rt\Identity\Auth\DTOs\PendingOAuthMergeDTO;
use He4rt\Identity\ExternalIdentity\Data\ClientAccessManager;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Events\ExternalIdentityConnected;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;

test('rolls back the account merge when the oauth connection cannot finish', function (): void {
    $currentUser = User::factory()->create();
    $targetUser = User::factory()->create();
    $discordIdentity = ExternalIdentity::factory()->create([
        'model_type' => (new User)->getMorphClass(),
        'model_id' => $targetUser->id,
        'provider' => IdentityProvider::Discord,
        'external_account_id' => 'discord-rollback',
        'credentials' => ClientAccessManager::make(),
        'connected_at' => null,
        'metadata' => ['username' => 'imported-user'],
    ]);
    $currentIdentity = ExternalIdentity::factory()->create([
        'model_type' => (new User)->getMorphClass(),
        'model_id' => $currentUser->id,
        'provider' => IdentityProvider::GitHub,
        'external_account_id' => 'github-rollback',
    ]);
    $pending = new PendingOAuthMergeDTO(
        conflictingUserId: $targetUser->id,
        provider: IdentityProvider::Discord,
        providerId: 'discord-rollback',
        credentials: ClientAccessManager::make(
            accessToken: Crypt::encrypt('access-token'),
            refreshToken: Crypt::encrypt('refresh-token'),
            expiresIn: Crypt::encrypt('3600'),
        ),
        metadata: ['username' => 'oauth-user'],
    );

    Event::listen(
        ExternalIdentityConnected::class,
        static fn () => throw new RuntimeException('connection listener failed'),
    );

    expect(fn () => resolve(ConfirmOAuthMerge::class)->execute($currentUser, $pending))
        ->toThrow(RuntimeException::class, 'connection listener failed');

    $discordIdentity->refresh();
    $currentIdentity->refresh();

    expect(User::query()->find($currentUser->id))->not->toBeNull()
        ->and(User::query()->find($targetUser->id))->not->toBeNull()
        ->and($discordIdentity->model_id)->toBe($targetUser->id)
        ->and($discordIdentity->metadata)->toBe(['username' => 'imported-user'])
        ->and($discordIdentity->credentials->getAccessToken())->toBeNull()
        ->and($discordIdentity->connected_at)->toBeNull()
        ->and($currentIdentity->model_id)->toBe($currentUser->id);
});

test('rejects an incomplete pending oauth merge payload', function (): void {
    expect(resolve(ResolvePendingOAuthMerge::class)->execute([
        'conflicting_user_id' => 'target-user',
        'provider' => IdentityProvider::Discord->value,
        'credentials' => ['access_token' => 'missing-refresh-token'],
        'provider_id' => 'discord-id',
        'metadata' => [],
    ]))->toBeNull();
});

test('resolves the pending oauth merge session payload', function (): void {
    $pending = resolve(ResolvePendingOAuthMerge::class)->execute([
        'conflicting_user_id' => 'target-user',
        'provider' => IdentityProvider::Discord->value,
        'credentials' => [
            'access_token' => Crypt::encrypt('access-token'),
            'refresh_token' => Crypt::encrypt('refresh-token'),
            'expires_in' => Crypt::encrypt('3600'),
        ],
        'provider_id' => 'discord-id',
        'metadata' => [
            'username' => 'discord-user',
            'global_name' => 'Discord User',
        ],
    ]);

    expect($pending)->toBeInstanceOf(PendingOAuthMergeDTO::class)
        ->and($pending?->credentials->getAccessToken())->toBe('access-token')
        ->and($pending?->credentials->getRefreshToken())->toBe('refresh-token')
        ->and($pending?->credentials->getExpiresIn())->toBe(3_600)
        ->and($pending?->metadata)->toBe([
            'username' => 'discord-user',
            'global_name' => 'Discord User',
        ]);
});
