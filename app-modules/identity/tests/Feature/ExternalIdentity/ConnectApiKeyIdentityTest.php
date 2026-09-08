<?php

declare(strict_types=1);

use He4rt\Identity\ExternalIdentity\Actions\ConnectApiKeyIdentity;
use He4rt\Identity\ExternalIdentity\Enums\CredentialsType;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Events\ExternalIdentityConnected;
use He4rt\Identity\ExternalIdentity\Exceptions\InvalidApiKeyException;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

function fakeDevToProfile(int $id = 12_345, string $username = 'danielhe4rt'): void
{
    Http::fake([
        'dev.to/api/users/me' => Http::response([
            'id' => $id,
            'username' => $username,
            'name' => 'Daniel',
            'profile_image' => 'https://example.com/avatar.png',
        ]),
    ]);
}

test('connects the provider and stores the profile the sync looks up', function (): void {
    fakeDevToProfile();

    $user = User::factory()->create();

    $identity = resolve(ConnectApiKeyIdentity::class)
        ->execute($user, IdentityProvider::DevTo, 'my-secret-key');

    expect($identity->provider)->toBe(IdentityProvider::DevTo)
        ->and($identity->credentials_type)->toBe(CredentialsType::ApiKey)
        ->and($identity->external_account_id)->toBe('12345')
        ->and($identity->metadata['username'])->toBe('danielhe4rt')
        ->and($identity->metadata['avatar'])->toBe('https://example.com/avatar.png')
        ->and($identity->connected_at)->not->toBeNull()
        ->and($identity->disconnected_at)->toBeNull()
        ->and($identity->model_id)->toBe($user->id);
});

test('stores the key encrypted and reads it back', function (): void {
    fakeDevToProfile();

    $user = User::factory()->create();

    $identity = resolve(ConnectApiKeyIdentity::class)
        ->execute($user, IdentityProvider::DevTo, 'my-secret-key');

    expect($identity->credentials->getApiKey())->toBe('my-secret-key');

    // coluna crua, sem passar pelo cast AsCredentials
    $rawCredentials = DB::table('external_identities')
        ->where('id', $identity->id)
        ->value('credentials');

    expect($rawCredentials)->toBeString()
        ->and($rawCredentials)->not->toContain('my-secret-key')
        ->and(json_decode($rawCredentials, associative: true)['api_key'])->not->toBe('my-secret-key');
});

test('replacing the key updates the same identity instead of duplicating it', function (): void {
    fakeDevToProfile();

    $user = User::factory()->create();
    $action = resolve(ConnectApiKeyIdentity::class);

    $first = $action->execute($user, IdentityProvider::DevTo, 'old-key');
    $second = $action->execute($user, IdentityProvider::DevTo, 'new-key');

    expect($second->id)->toBe($first->id)
        ->and($second->credentials->getApiKey())->toBe('new-key')
        ->and(ExternalIdentity::query()->where('provider', IdentityProvider::DevTo)->count())->toBe(1);
});

test('reconnecting clears the disconnected timestamp', function (): void {
    fakeDevToProfile();

    $user = User::factory()->create();
    $action = resolve(ConnectApiKeyIdentity::class);

    $identity = $action->execute($user, IdentityProvider::DevTo, 'old-key');
    $identity->update(['disconnected_at' => now()]);

    $reconnected = $action->execute($user, IdentityProvider::DevTo, 'new-key');

    expect($reconnected->id)->toBe($identity->id)
        ->and($reconnected->disconnected_at)->toBeNull()
        ->and($reconnected->isConnected())->toBeTrue();
});

test('an invalid key leaves the database untouched', function (): void {
    Http::fake([
        'dev.to/api/users/me' => Http::response(['error' => 'unauthorized'], 401),
    ]);

    $user = User::factory()->create();

    expect(fn () => resolve(ConnectApiKeyIdentity::class)
        ->execute($user, IdentityProvider::DevTo, 'nope'))
        ->toThrow(InvalidApiKeyException::class);

    expect(ExternalIdentity::query()->count())->toBe(0);
});

test('refuses a provider that authenticates via oauth', function (): void {
    $user = User::factory()->create();

    expect(fn () => resolve(ConnectApiKeyIdentity::class)
        ->execute($user, IdentityProvider::GitHub, 'any-key'))
        ->toThrow(InvalidArgumentException::class);

    expect(ExternalIdentity::query()->count())->toBe(0);
});

test('connecting via api key dispatches ExternalIdentityConnected', function (): void {
    fakeDevToProfile();
    Event::fake([ExternalIdentityConnected::class]);

    $user = User::factory()->create();

    $identity = resolve(ConnectApiKeyIdentity::class)
        ->execute($user, IdentityProvider::DevTo, 'my-secret-key');

    Event::assertDispatched(fn (ExternalIdentityConnected $event): bool => $event->identity->id === $identity->id);
});

test('reconnecting via api key dispatches ExternalIdentityConnected again', function (): void {
    fakeDevToProfile();

    $user = User::factory()->create();
    $action = resolve(ConnectApiKeyIdentity::class);

    $first = $action->execute($user, IdentityProvider::DevTo, 'old-key');

    Event::fake([ExternalIdentityConnected::class]);

    $second = $action->execute($user, IdentityProvider::DevTo, 'new-key');

    expect($second->id)->toBe($first->id);

    Event::assertDispatched(fn (ExternalIdentityConnected $event): bool => $event->identity->id === $second->id);
});
