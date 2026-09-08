<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use He4rt\PanelAdmin\Twitch\Resources\TwitchSubscriptionResource\Pages\ListTwitchSubscriptions;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->user = User::factory()->superAdmin()->create();

    $this->actingAs($this->user);

    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

test('botão de conectar aponta para o fluxo OAuth da twitch no painel admin', function (): void {
    $expectedUrl = route('oauth.redirect', ['panel' => 'admin', 'provider' => 'twitch']);

    livewire(ListTwitchSubscriptions::class)
        ->assertActionExists('connect-twitch')
        ->assertActionHasUrl('connect-twitch', $expectedUrl)
        ->assertActionHasLabel('connect-twitch', __('panel-admin::twitch.connect.connect'));
});

test('quando já existe identidade twitch conectada, o botão mostra reconectar com o login', function (): void {
    ExternalIdentity::factory()->create([
        'model_type' => (new User)->getMorphClass(),
        'model_id' => $this->user->id,
        'provider' => IdentityProvider::Twitch,
        'connected_at' => now(),
        'disconnected_at' => null,
        'metadata' => ['login' => 'he4rtdevs'],
    ]);

    livewire(ListTwitchSubscriptions::class)
        ->assertActionExists('connect-twitch')
        ->assertActionHasLabel(
            'connect-twitch',
            __('panel-admin::twitch.connect.reconnect', ['login' => 'he4rtdevs']),
        );
});
