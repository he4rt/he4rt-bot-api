<?php

declare(strict_types=1);

use App\Livewire\ConnectionHub;
use Filament\Facades\Filament;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;

use function Pest\Livewire\livewire;

test('connection hub merge modal explains which account is kept and absorbed', function (): void {
    $keptUser = User::factory()->create([
        'username' => 'discord-user',
    ]);

    $currentUser = User::factory()->create([
        'username' => 'github-user',
    ]);

    ExternalIdentity::factory()->create([
        'model_type' => (new User)->getMorphClass(),
        'model_id' => $keptUser->id,
        'provider' => IdentityProvider::Discord,
        'external_account_id' => 'discord-123',
    ]);

    $this->actingAs($currentUser);
    Filament::setCurrentPanel(Filament::getPanel('app'));

    session()->put('oauth_merge_pending', [
        'conflicting_user_id' => $keptUser->id,
        'provider' => IdentityProvider::Discord->value,
        'provider_id' => 'discord-123',
        'credentials' => [],
        'metadata' => [
            'username' => 'discord-user',
            'global_name' => 'Discord User',
        ],
    ]);

    livewire(ConnectionHub::class)
        ->assertSee('Conta existente encontrada')
        ->assertSee('@ discord-user')
        ->assertSee('@ github-user')
        ->assertSee('será mantida como principal')
        ->assertSee('será absorvida e removida')
        ->assertSee('histórico já associado à conta mantida será preservado')
        ->assertSee('será autenticado novamente');
});
