<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use He4rt\Identity\User\Models\User;
use He4rt\PanelAdmin\Twitch\Resources\TwitchSubscriptionResource\Pages\ListTwitchSubscriptions;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user);

    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

test('o modal de registrar subscriptions monta mesmo sem eventsub_callback configurado', function (): void {
    // Reproduz o gatilho exato do crash: a chave presente-porém-nula quando
    // TWITCH_EVENTSUB_CALLBACK está unset. O callback deve derivar de app.url
    // em vez de estourar em config()->string() com valor nulo.
    config()->set('services.twitch.eventsub_callback');

    livewire(ListTwitchSubscriptions::class)
        ->mountAction('register-subscriptions')
        ->assertActionMounted('register-subscriptions')
        ->assertHasNoErrors();
});
