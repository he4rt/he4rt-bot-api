<?php

declare(strict_types=1);

use He4rt\Identity\ExternalIdentity\Actions\ResolveExternalIdentity;
use He4rt\Identity\ExternalIdentity\DTOs\ResolveUserProviderDTO;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;

test('ingestion creates a disconnected identity with public metadata', function (): void {
    $identity = resolve(ResolveExternalIdentity::class)->handle(
        ResolveUserProviderDTO::make([
            'provider' => IdentityProvider::Discord,
            'external_account_id' => '123456789',
            'model_type' => (new User)->getMorphClass(),
            'username' => 'discord-user',
            'email' => 'discord@example.com',
            'avatar' => 'avatar-hash',
        ]),
    );

    expect($identity->connected_at)->toBeNull()
        ->and($identity->credentials->getAccessToken())->toBeNull()
        ->and($identity->metadata)->toBe([
            'username' => 'discord-user',
            'email' => 'discord@example.com',
            'avatar' => 'avatar-hash',
        ]);
});

test('ingestion does not overwrite an authenticated identity', function (): void {
    $identity = ExternalIdentity::factory()->morphFor()->create([
        'provider' => IdentityProvider::Discord,
        'external_account_id' => '123456789',
        'metadata' => [
            'username' => 'oauth-user',
            'email' => 'oauth@example.com',
            'profile' => ['bio' => 'preserved'],
        ],
    ]);
    $accessToken = $identity->credentials->accessToken;
    $connectedAt = $identity->connected_at;

    $resolved = resolve(ResolveExternalIdentity::class)->handle(
        ResolveUserProviderDTO::make([
            'provider' => IdentityProvider::Discord,
            'external_account_id' => '123456789',
            'model_type' => (new User)->getMorphClass(),
            'username' => 'message-user',
        ]),
    );

    expect($resolved->id)->toBe($identity->id)
        ->and($resolved->metadata)->toMatchArray($identity->metadata)
        ->and($resolved->credentials->accessToken)->toBe($accessToken)
        ->and($resolved->connected_at?->equalTo($connectedAt))->toBeTrue();
});
