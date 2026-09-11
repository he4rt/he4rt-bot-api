<?php

declare(strict_types=1);

use He4rt\Identity\Auth\Actions\AttachProviderToUser;
use He4rt\Identity\Auth\DTOs\OAuthAccessDTO;
use He4rt\Identity\Auth\DTOs\OAuthUserDTO;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Events\ExternalIdentityConnected;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use Illuminate\Support\Facades\Event;

function attachOAuthUser(
    string $providerId = '12345',
    IdentityProvider $provider = IdentityProvider::GitHub,
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

    return new class($credentials, $providerId, $provider, $username, $name, $email, avatarUrl: null) extends OAuthUserDTO
    {
        public static function make(OAuthAccessDTO $credentials, array $payload): self
        {
            return new self($credentials, '', IdentityProvider::GitHub, '', '', null, null);
        }
    };
}

test('attaching a provider dispatches ExternalIdentityConnected', function (): void {
    Event::fake([ExternalIdentityConnected::class]);

    $user = User::factory()->create();

    $identity = resolve(AttachProviderToUser::class)->execute(
        $user,
        attachOAuthUser(providerId: '999', provider: IdentityProvider::GitHub),
        (attachOAuthUser())->credentials,
    );

    Event::assertDispatched(fn (ExternalIdentityConnected $event): bool => $event->identity->id === $identity->id);
});

test('reattaching (reconnect) dispatches ExternalIdentityConnected again', function (): void {
    $user = User::factory()->create();
    $action = resolve(AttachProviderToUser::class);

    $first = $action->execute(
        $user,
        attachOAuthUser(providerId: '999', provider: IdentityProvider::GitHub),
        (attachOAuthUser())->credentials,
    );

    Event::fake([ExternalIdentityConnected::class]);

    $second = $action->execute(
        $user,
        attachOAuthUser(providerId: '999', provider: IdentityProvider::GitHub),
        (attachOAuthUser())->credentials,
    );

    expect($second->id)->toBe($first->id)
        ->and(ExternalIdentity::query()->where('provider', IdentityProvider::GitHub)->count())->toBe(1);

    Event::assertDispatched(fn (ExternalIdentityConnected $event): bool => $event->identity->id === $second->id);
});

test('reattaching preserves metadata owned by other ingestion flows', function (): void {
    $user = User::factory()->create();

    $existing = ExternalIdentity::factory()->morphFor()->create([
        'model_id' => $user->id,
        'provider' => IdentityProvider::GitHub,
        'external_account_id' => '999',
        'metadata' => [
            'email' => 'preserved@example.com',
            'username' => 'old-name',
            'avatar' => 'old-avatar',
            'profile' => ['bio' => 'Imported profile'],
            'badges' => [['id' => 'contributor']],
        ],
    ]);

    $identity = resolve(AttachProviderToUser::class)->execute(
        $user,
        attachOAuthUser(
            providerId: '999',
            provider: IdentityProvider::GitHub,
            username: 'new-name',
            email: null,
        ),
        (attachOAuthUser())->credentials,
    );

    expect($identity->id)->toBe($existing->id)
        ->and($identity->metadata)->toMatchArray([
            'email' => 'preserved@example.com',
            'username' => 'new-name',
            'avatar' => 'old-avatar',
            'profile' => ['bio' => 'Imported profile'],
            'badges' => [['id' => 'contributor']],
        ])
        ->and($identity->credentials->getAccessToken())->toBe('token')
        ->and((string) $identity->model_id)->toBe((string) $user->id);
});
