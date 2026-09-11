<?php

declare(strict_types=1);

use App\Livewire\ConnectionHub;
use Filament\Facades\Filament;
use He4rt\Identity\Auth\Actions\HandleOAuthCallbackAction;
use He4rt\Identity\Auth\DTOs\MergeConflictDTO;
use He4rt\Identity\Auth\DTOs\OAuthStateDTO;
use He4rt\Identity\Auth\Enums\OAuthIntent;
use He4rt\Identity\ExternalIdentity\Data\ClientAccessManager;
use He4rt\Identity\ExternalIdentity\Enums\CredentialsType;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Events\ExternalIdentityConnected;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use He4rt\IntegrationDiscord\OAuth\DiscordOAuthAccessDTO;
use He4rt\IntegrationDiscord\OAuth\DiscordOAuthClient;
use He4rt\IntegrationDiscord\OAuth\DiscordOAuthUser;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;

use function Pest\Livewire\livewire;

/**
 * @param  array<string, mixed>  $metadata
 * @return array<string, mixed>
 */
function connectionHubOAuthMergePayload(
    string $conflictingUserId,
    string $providerId,
    array $metadata = ['username' => 'oauth-user'],
): array {
    return [
        'conflicting_user_id' => $conflictingUserId,
        'provider' => IdentityProvider::Discord->value,
        'provider_id' => $providerId,
        'credentials' => [
            'access_token' => Crypt::encrypt('access-token'),
            'refresh_token' => Crypt::encrypt('refresh-token'),
            'expires_in' => Crypt::encrypt('3600'),
        ],
        'metadata' => $metadata,
    ];
}

test('merge confirmation modal exposes accessible dialog semantics', function (): void {
    $currentUser = User::factory()->create();
    $mergeTarget = User::factory()->create();

    $this->actingAs($currentUser);

    session()->put('oauth_merge_pending', [
        'conflicting_user_id' => $mergeTarget->id,
    ]);

    livewire(ConnectionHub::class)
        ->assertSet('showMergeModal', value: true)
        ->assertSeeHtml('role="dialog"')
        ->assertSeeHtml('aria-modal="true"')
        ->assertSeeHtml('@keydown.escape.window="$wire.cancelMerge()"');
});

test('confirming an oauth merge connects the imported identity without losing its data', function (): void {
    Event::fake([ExternalIdentityConnected::class]);

    $currentUser = User::factory()->create([
        'username' => 'current-user',
        'name' => 'Current User',
        'email' => 'current@example.com',
    ]);
    $importedUser = User::factory()->create([
        'username' => 'imported-user',
        'name' => 'imported-user',
        'email' => null,
        'first_login_at' => null,
    ]);
    $importedIdentity = ExternalIdentity::factory()->create([
        'model_type' => (new User)->getMorphClass(),
        'model_id' => $importedUser->id,
        'provider' => IdentityProvider::Discord,
        'external_account_id' => '49615312957476864',
        'credentials' => ClientAccessManager::make(),
        'connected_at' => null,
        'connected_by' => null,
        'metadata' => [
            'username' => 'imported-user',
            'global_name' => 'Imported User',
            'avatar' => 'profile-avatar',
            'user' => [
                'id' => '49615312957476864',
                'username' => 'imported-user',
                'global_name' => 'Imported User',
                'avatar' => 'profile-avatar',
                'public_flags' => 64,
            ],
            'badges' => [['id' => 'legacy_username']],
        ],
    ]);
    $currentIdentity = ExternalIdentity::factory()->create([
        'model_type' => (new User)->getMorphClass(),
        'model_id' => $currentUser->id,
        'provider' => IdentityProvider::GitHub,
        'external_account_id' => 'github-123',
    ]);

    $access = DiscordOAuthAccessDTO::make([
        'access_token' => 'discord-access-token',
        'refresh_token' => 'discord-refresh-token',
        'expires_in' => 3_600,
    ]);
    $oauthUser = DiscordOAuthUser::make($access, [
        'id' => '49615312957476864',
        'username' => 'oauth-user',
        'global_name' => 'OAuth User',
        'email' => 'discord@example.com',
        'avatar' => null,
    ]);
    $client = Mockery::mock(DiscordOAuthClient::class);
    $client->shouldReceive('auth')->once()->with('auth-code')->andReturn($access);
    $client->shouldReceive('getAuthenticatedUser')->once()->with($access)->andReturn($oauthUser);
    app()->instance(DiscordOAuthClient::class, $client);

    $this->actingAs($currentUser);
    Filament::setCurrentPanel(Filament::getPanel('app'));

    $result = resolve(HandleOAuthCallbackAction::class)->execute(
        new OAuthStateDTO(
            intent: OAuthIntent::Link,
            provider: IdentityProvider::Discord,
            panel: 'app',
            returnUrl: 'https://he4rt.test/app/profile',
        ),
        IdentityProvider::Discord,
        'auth-code',
    );

    $mergeConflict = $result->mergeConflict;

    expect($mergeConflict)->toBeInstanceOf(MergeConflictDTO::class);

    throw_unless($mergeConflict instanceof MergeConflictDTO, RuntimeException::class, 'Expected an OAuth merge conflict.');

    $sessionPayload = $mergeConflict->toSession();

    expect($sessionPayload['credentials']['access_token'])->not->toBe('discord-access-token')
        ->and($sessionPayload['credentials']['refresh_token'])->not->toBe('discord-refresh-token')
        ->and($sessionPayload)->not->toHaveKey('oauth_user')
        ->and($sessionPayload['provider_id'])->toBe('49615312957476864');

    session()->put('oauth_merge_pending', $sessionPayload);

    livewire(ConnectionHub::class)
        ->assertSet('showMergeModal', value: true)
        ->assertSet('mergeTargetId', $importedUser->id)
        ->assertDontSee('discord-access-token')
        ->assertDontSee('discord-refresh-token')
        ->call('confirmMerge')
        ->assertSet('showMergeModal', value: false)
        ->assertNotified();

    $importedIdentity->refresh();
    $currentIdentity->refresh();

    expect($importedIdentity->model_id)->toBe($importedUser->id)
        ->and($importedIdentity->credentials->getAccessToken())->toBe('discord-access-token')
        ->and($importedIdentity->credentials->getRefreshToken())->toBe('discord-refresh-token')
        ->and($importedIdentity->credentials->getExpiresIn())->toBe(3_600)
        ->and($importedIdentity->connected_at)->not->toBeNull()
        ->and($importedIdentity->disconnected_at)->toBeNull()
        ->and($importedIdentity->connected_by)->toBe($importedUser->id)
        ->and($importedIdentity->metadata)->toMatchArray([
            'username' => 'oauth-user',
            'global_name' => 'OAuth User',
            'email' => 'discord@example.com',
            'avatar' => null,
            'user' => [
                'id' => '49615312957476864',
                'username' => 'imported-user',
                'global_name' => 'Imported User',
                'avatar' => 'profile-avatar',
                'public_flags' => 64,
            ],
            'badges' => [['id' => 'legacy_username']],
        ])
        ->and($currentIdentity->model_id)->toBe($importedUser->id)
        ->and(User::query()->find($currentUser->id))->toBeNull()
        ->and(auth()->id())->toBe($importedUser->id)
        ->and(session()->has('oauth_merge_pending'))->toBeFalse()
        ->and(ExternalIdentity::query()
            ->where('provider', IdentityProvider::Discord)
            ->where('external_account_id', '49615312957476864')
            ->count())->toBe(1);

    Event::assertDispatched(
        fn (ExternalIdentityConnected $event): bool => $event->identity->is($importedIdentity),
    );
});

test('canceling an oauth merge leaves both accounts and the imported identity untouched', function (): void {
    Event::fake([ExternalIdentityConnected::class]);

    $currentUser = User::factory()->create();
    $importedUser = User::factory()->create();
    $identity = ExternalIdentity::factory()->create([
        'model_type' => (new User)->getMorphClass(),
        'model_id' => $importedUser->id,
        'provider' => IdentityProvider::Discord,
        'external_account_id' => 'discord-cancel',
        'credentials' => ClientAccessManager::make(),
        'connected_at' => null,
        'metadata' => ['username' => 'preserved-user'],
    ]);

    $this->actingAs($currentUser);
    session()->put('oauth_merge_pending', connectionHubOAuthMergePayload(
        conflictingUserId: $importedUser->id,
        providerId: 'discord-cancel',
        metadata: ['username' => 'unused-user'],
    ));

    livewire(ConnectionHub::class)->call('cancelMerge');

    $identity->refresh();

    expect(User::query()->find($currentUser->id))->not->toBeNull()
        ->and(User::query()->find($importedUser->id))->not->toBeNull()
        ->and($identity->model_id)->toBe($importedUser->id)
        ->and($identity->metadata)->toBe(['username' => 'preserved-user'])
        ->and($identity->credentials->getAccessToken())->toBeNull()
        ->and($identity->connected_at)->toBeNull()
        ->and(session()->has('oauth_merge_pending'))->toBeFalse();

    Event::assertNotDispatched(ExternalIdentityConnected::class);
});

test('oauth merge aborts when the conflicting identity no longer belongs to the target user', function (): void {
    $currentUser = User::factory()->create();
    $targetUser = User::factory()->create();

    $this->actingAs($currentUser);
    session()->put('oauth_merge_pending', connectionHubOAuthMergePayload(
        conflictingUserId: $targetUser->id,
        providerId: 'missing-discord-identity',
        metadata: ['username' => 'unused-user'],
    ));

    livewire(ConnectionHub::class)->call('confirmMerge');

    expect(User::query()->find($currentUser->id))->not->toBeNull()
        ->and(User::query()->find($targetUser->id))->not->toBeNull()
        ->and(auth()->id())->toBe($currentUser->id)
        ->and(session()->has('oauth_merge_pending'))->toBeFalse();
});

test('oauth merge payload cannot be changed through livewire state', function (): void {
    $currentUser = User::factory()->create();
    $targetUser = User::factory()->create();
    $otherUser = User::factory()->create();

    $this->actingAs($currentUser);
    session()->put('oauth_merge_pending', [
        'conflicting_user_id' => $targetUser->id,
    ]);

    expect(fn () => livewire(ConnectionHub::class)
        ->set('mergeTargetId', $otherUser->id))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});

test('an api key provider opens the modal instead of redirecting', function (): void {
    $this->actingAs(User::factory()->create());

    livewire(ConnectionHub::class)
        ->call('connect', IdentityProvider::DevTo)
        ->assertNoRedirect()
        ->assertSet('showApiKeyModal', value: true)
        ->assertSet('apiKeyProvider', 'devto');
});

test('an oauth provider still redirects to the oauth flow', function (): void {
    $this->actingAs(User::factory()->create());

    livewire(ConnectionHub::class)
        ->call('connect', IdentityProvider::GitHub)
        ->assertSet('showApiKeyModal', value: false)
        ->assertRedirect();
});

test('saving a valid key connects the provider and closes the modal', function (): void {
    Http::fake([
        'dev.to/api/users/me' => Http::response([
            'id' => 12_345,
            'username' => 'danielhe4rt',
            'name' => 'Daniel',
        ]),
    ]);

    $user = User::factory()->create();
    $this->actingAs($user);

    livewire(ConnectionHub::class)
        ->call('connect', IdentityProvider::DevTo)
        ->set('apiKey', 'a-valid-api-key')
        ->call('saveApiKey')
        ->assertHasNoErrors()
        ->assertSet('showApiKeyModal', value: false)
        ->assertSet('apiKey', '')
        ->assertNotified();

    $identity = ExternalIdentity::query()
        ->where('model_id', $user->id)
        ->where('provider', IdentityProvider::DevTo)
        ->sole();

    expect($identity->credentials_type)->toBe(CredentialsType::ApiKey)
        ->and($identity->metadata['username'])->toBe('danielhe4rt');
});

test('an invalid key keeps the modal open with an error and stores nothing', function (): void {
    Http::fake([
        'dev.to/api/users/me' => Http::response(['error' => 'unauthorized'], 401),
    ]);

    $this->actingAs(User::factory()->create());

    livewire(ConnectionHub::class)
        ->call('connect', IdentityProvider::DevTo)
        ->set('apiKey', 'a-wrong-api-key')
        ->call('saveApiKey')
        ->assertHasErrors('apiKey')
        ->assertSet('showApiKeyModal', value: true)
        ->assertSet('apiKey', 'a-wrong-api-key');

    expect(ExternalIdentity::query()->where('provider', IdentityProvider::DevTo)->count())->toBe(0);
});

test('an empty key is rejected before any request is made', function (): void {
    Http::fake();

    $this->actingAs(User::factory()->create());

    livewire(ConnectionHub::class)
        ->call('connect', IdentityProvider::DevTo)
        ->set('apiKey', '')
        ->call('saveApiKey')
        ->assertHasErrors(['apiKey' => 'required'])
        ->assertSet('showApiKeyModal', value: true);

    Http::assertNothingSent();
});

test('the connection hub groups providers by authentication method', function (): void {
    $this->actingAs(User::factory()->create());

    livewire(ConnectionHub::class)
        ->assertSee(CredentialsType::OAuth2->getLabel())
        ->assertSee(CredentialsType::ApiKey->getLabel())
        ->assertSee(IdentityProvider::DevTo->getLabel());
});
