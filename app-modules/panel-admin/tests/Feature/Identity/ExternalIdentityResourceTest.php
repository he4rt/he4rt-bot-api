<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use He4rt\PanelAdmin\Filament\Resources\ExternalIdentities\Pages\ListExternalIdentities;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    config([
        'app.display_timezone' => 'America/Sao_Paulo',
    ]);

    $this->admin = User::factory()->superAdmin()->create();

    $this->actingAs($this->admin);

    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

test('a listagem não expõe mais a coluna de metadata', function (): void {
    livewire(ListExternalIdentities::class)
        ->loadTable()
        ->assertTableColumnDoesNotExist('metadata');
});

test('o filtro de provider restringe ao provedor escolhido', function (): void {
    $discord = ExternalIdentity::factory()->create(['provider' => IdentityProvider::Discord]);
    $twitch = ExternalIdentity::factory()->create(['provider' => IdentityProvider::Twitch]);

    livewire(ListExternalIdentities::class)
        ->loadTable()
        ->filterTable('provider', [IdentityProvider::Discord->value])
        ->assertCanSeeTableRecords([$discord])
        ->assertCanNotSeeTableRecords([$twitch]);
});

test('o filtro de conexão ativa separa conectadas de desconectadas', function (): void {
    $active = ExternalIdentity::factory()->create([
        'provider' => IdentityProvider::Discord,
        'disconnected_at' => null,
    ]);
    $disconnected = ExternalIdentity::factory()->create([
        'provider' => IdentityProvider::Discord,
        'disconnected_at' => now()->subDay(),
    ]);

    livewire(ListExternalIdentities::class)
        ->loadTable()
        ->filterTable('connection_state', true)
        ->assertCanSeeTableRecords([$active])
        ->assertCanNotSeeTableRecords([$disconnected]);

    livewire(ListExternalIdentities::class)
        ->loadTable()
        ->filterTable('connection_state', false)
        ->assertCanSeeTableRecords([$disconnected])
        ->assertCanNotSeeTableRecords([$active]);
});
