<?php

declare(strict_types=1);

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\IntegrationDiscord\OAuth\DiscordOAuthAccessDTO;
use He4rt\IntegrationDiscord\OAuth\DiscordOAuthClient;
use He4rt\IntegrationDiscord\OAuth\DiscordOAuthUser;
use He4rt\IntegrationDiscord\Transport\DiscordOAuthConnector;
use He4rt\IntegrationDiscord\Transport\Requests\OAuth\ExchangeCodeForToken;
use He4rt\IntegrationDiscord\Transport\Requests\OAuth\GetCurrentUser;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

it('exchanges code for token and returns DiscordOAuthAccessDTO', function (): void {
    $mockClient = new MockClient([
        ExchangeCodeForToken::class => MockResponse::make([
            'access_token' => 'test-access-token',
            'refresh_token' => 'test-refresh-token',
            'expires_in' => 604_800,
        ]),
    ]);

    $connector = new DiscordOAuthConnector('client-id', 'client-secret', 'https://example.com/callback');
    $connector->withMockClient($mockClient);

    $client = new DiscordOAuthClient($connector);
    $result = $client->auth('test-code');

    expect($result)
        ->toBeInstanceOf(DiscordOAuthAccessDTO::class)
        ->and($result->accessToken)->toBe('test-access-token')
        ->and($result->refreshToken)->toBe('test-refresh-token')
        ->and($result->expiresIn)->toBe(604_800);
});

it('gets authenticated user and returns DiscordOAuthUser', function (): void {
    $mockClient = new MockClient([
        GetCurrentUser::class => MockResponse::make([
            'id' => '123456789',
            'username' => 'testuser',
            'global_name' => 'Test User',
            'email' => 'test@example.com',
            'avatar' => 'abc123',
        ]),
    ]);

    $connector = new DiscordOAuthConnector('client-id', 'client-secret', 'https://example.com/callback');
    $connector->withMockClient($mockClient);

    $credentials = DiscordOAuthAccessDTO::make([
        'access_token' => 'test-access-token',
        'refresh_token' => 'test-refresh-token',
        'expires_in' => 604_800,
    ]);

    $client = new DiscordOAuthClient($connector);
    $result = $client->getAuthenticatedUser($credentials);

    expect($result)
        ->toBeInstanceOf(DiscordOAuthUser::class)
        ->and($result->username)->toBe('testuser')
        ->and($result->name)->toBe('Test User')
        ->and($result->email)->toBe('test@example.com')
        ->and($result->providerId)->toBe('123456789')
        ->and($result->toMetadata())->toBe([
            'email' => 'test@example.com',
            'avatar' => 'https://cdn.discordapp.com/avatars/123456789/abc123.png',
            'username' => 'testuser',
            'global_name' => 'Test User',
        ]);
});

it('normalizes an authenticated Discord user without email or avatar', function (): void {
    $credentials = DiscordOAuthAccessDTO::make([
        'access_token' => 'test-access-token',
        'refresh_token' => 'test-refresh-token',
        'expires_in' => 604_800,
    ]);

    $user = DiscordOAuthUser::make($credentials, [
        'id' => '123456789',
        'username' => 'testuser',
        'global_name' => null,
        'avatar' => null,
    ]);

    expect($user->email)->toBeNull()
        ->and($user->avatarUrl)->toBeNull()
        ->and($user->toMetadata())->toBe([
            'avatar' => null,
            'username' => 'testuser',
            'global_name' => 'testuser',
        ]);
});

it('omits an unavailable avatar from authenticated user metadata', function (): void {
    $credentials = DiscordOAuthAccessDTO::make([
        'access_token' => 'test-access-token',
        'refresh_token' => 'test-refresh-token',
        'expires_in' => 604_800,
    ]);

    $user = DiscordOAuthUser::make($credentials, [
        'id' => '123456789',
        'username' => 'testuser',
        'global_name' => 'Test User',
    ]);

    expect($user->avatarUrl)->toBeNull()
        ->and($user->toMetadata())->not->toHaveKey('avatar');
});

it('keeps the previous seven argument constructor compatible', function (): void {
    $credentials = DiscordOAuthAccessDTO::make([
        'access_token' => 'test-access-token',
        'refresh_token' => 'test-refresh-token',
        'expires_in' => 604_800,
    ]);

    $user = new DiscordOAuthUser(
        $credentials,
        '123456789',
        IdentityProvider::Discord,
        'testuser',
        'Test User',
        email: null,
        avatarUrl: null,
    );

    expect($user->toMetadata())->toBe([
        'username' => 'testuser',
        'global_name' => 'Test User',
    ]);
});

it('generates correct redirect url', function (): void {
    config()->set('services.discord.scopes', 'identify email');
    config()->set('app.url', 'http://localhost:8000');

    $connector = new DiscordOAuthConnector('my-client-id', 'client-secret', 'https://example.com/callback');
    $client = new DiscordOAuthClient($connector);

    $url = $client->redirectUrl();

    $expectedCallback = urlencode('http://localhost:8000/auth/oauth/discord');

    expect($url)
        ->toContain('https://discord.com/oauth2/authorize')
        ->toContain('client_id=my-client-id')
        ->toContain('response_type=code')
        ->toContain('redirect_uri='.$expectedCallback)
        ->toContain('scope=identify+email');
});
