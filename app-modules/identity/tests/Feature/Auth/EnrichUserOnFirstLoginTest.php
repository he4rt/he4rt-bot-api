<?php

declare(strict_types=1);

use He4rt\Identity\Auth\Actions\EnrichUserOnFirstLogin;
use He4rt\Identity\Auth\DTOs\OAuthAccessDTO;
use He4rt\Identity\Auth\DTOs\OAuthUserDTO;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\User\Models\User;

function makeOAuthUserForEnrich(
    string $username = 'testuser',
    string $name = 'Test User',
    ?string $email = 'test@example.com',
): OAuthUserDTO {
    $credentials = new class('token', 'refresh', 3_600) extends OAuthAccessDTO
    {
        public static function make(array $payload): self
        {
            return new self('token', 'refresh', 3_600);
        }
    };

    return new class($credentials, '12345', IdentityProvider::GitHub, $username, $name, $email, avatarUrl: null) extends OAuthUserDTO
    {
        public static function make(OAuthAccessDTO $credentials, array $payload): self
        {
            return new self($credentials, '', IdentityProvider::GitHub, '', '', null, null);
        }
    };
}

test('enriches user on first login with email and name', function (): void {
    $user = User::factory()->create([
        'email' => null,
        'name' => 'oldname',
        'first_login_at' => null,
    ]);

    $action = new EnrichUserOnFirstLogin();
    $result = $action->execute(
        $user,
        makeOAuthUserForEnrich(name: 'Daniel Reis', email: 'daniel@example.com'),
    );

    expect($result->email)->toBe('daniel@example.com')
        ->and($result->name)->toBe('Daniel Reis')
        ->and($result->first_login_at)->not->toBeNull();
});

test('updates username on first login when available', function (): void {
    $user = User::factory()->create([
        'username' => 'old-username',
        'first_login_at' => null,
    ]);

    $action = new EnrichUserOnFirstLogin();
    $result = $action->execute(
        $user,
        makeOAuthUserForEnrich(username: 'new-username'),
    );

    expect($result->username)->toBe('new-username');
});

test('skips username update when it would collide', function (): void {
    User::factory()->create(['username' => 'taken-username']);
    $user = User::factory()->create([
        'username' => 'original',
        'first_login_at' => null,
    ]);

    $action = new EnrichUserOnFirstLogin();
    $result = $action->execute(
        $user,
        makeOAuthUserForEnrich(username: 'taken-username'),
    );

    expect($result->username)->toBe('original');
});

test('does not update user when first_login_at is already set', function (): void {
    $user = User::factory()->create([
        'email' => 'old@example.com',
        'name' => 'Old Name',
        'username' => 'olduser',
        'first_login_at' => now()->subMonth(),
    ]);

    $action = new EnrichUserOnFirstLogin();
    $result = $action->execute(
        $user,
        makeOAuthUserForEnrich(username: 'newuser', name: 'New Name', email: 'new@example.com'),
    );

    expect($result->email)->toBe('old@example.com')
        ->and($result->name)->toBe('Old Name')
        ->and($result->username)->toBe('olduser');
});

test('does not overwrite email with null', function (): void {
    $user = User::factory()->create([
        'email' => 'existing@example.com',
        'first_login_at' => null,
    ]);

    $action = new EnrichUserOnFirstLogin();
    $result = $action->execute(
        $user,
        makeOAuthUserForEnrich(email: null),
    );

    expect($result->email)->toBe('existing@example.com')
        ->and($result->first_login_at)->not->toBeNull();
});

test('does not overwrite username when username_manually_set_at is present', function (): void {
    $user = User::factory()->create([
        'username' => 'custom-username',
        'first_login_at' => null,
        'username_manually_set_at' => now()->subDay(),
    ]);

    $action = new EnrichUserOnFirstLogin();
    $result = $action->execute(
        $user,
        makeOAuthUserForEnrich(username: 'oauth-username'),
    );

    expect($result->username)->toBe('custom-username');
});
