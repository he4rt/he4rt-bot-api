<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Filament\Tables\Table as FilamentTable;
use He4rt\Community\Retrospective\Enums\RetrospectiveStatus;
use He4rt\Community\Retrospective\Models\Retrospective;
use He4rt\Identity\User\Models\User;
use He4rt\PanelAdmin\Filament\Resources\Retrospectives\Pages\CreateRetrospective;
use He4rt\PanelAdmin\Filament\Resources\Retrospectives\Pages\ListRetrospectives;
use He4rt\PanelAdmin\Filament\Resources\Retrospectives\RetrospectiveResource;
use He4rt\PanelAdmin\Filament\Resources\Retrospectives\Tables\RetrospectivesTable;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user);

    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

test('admin cria uma retrospectiva como rascunho com a ordem das fontes semeada', function (): void {
    livewire(CreateRetrospective::class)
        ->fillForm([
            'title' => 'Retro de Junho',
            'since' => '2026-06-01 00:00:00',
            'until' => '2026-06-30 23:59:59',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $retrospective = Retrospective::query()->where('title', 'Retro de Junho')->sole();

    expect($retrospective->status)->toBe(RetrospectiveStatus::Draft)
        ->and($retrospective->snapshot)->toBeNull()
        ->and($retrospective->deck_config->order)->toEqualCanonicalizing(['github', 'discord']);
});

test('criar exige título e período, e não pede curadoria', function (): void {
    livewire(CreateRetrospective::class)
        ->fillForm(['title' => ''])
        ->call('create')
        ->assertHasFormErrors(['title' => 'required', 'since' => 'required', 'until' => 'required'])
        ->assertFormFieldDoesNotExist('deck_sources')
        ->assertFormFieldDoesNotExist('deck_exclusions');
});

test('lista as retrospectivas cadastradas', function (): void {
    $retrospective = Retrospective::factory()->create(['title' => 'Retro X']);

    livewire(ListRetrospectives::class)
        ->loadTable()
        ->assertCanSeeTableRecords([$retrospective]);
});

test('a ação de editar da tabela leva ao Deck Builder', function (): void {
    $retrospective = Retrospective::factory()->create();

    expect(RetrospectiveResource::getUrl('edit', ['record' => $retrospective]))->toEndWith('/deck');
});

test('a lista acompanha a publicação em segundo plano sem recarregar', function (): void {
    $retrospective = Retrospective::factory()->create(['status' => RetrospectiveStatus::Publishing]);

    $component = livewire(ListRetrospectives::class)
        ->loadTable()
        ->assertSee(RetrospectiveStatus::Publishing->getLabel());

    $retrospective->update(['status' => RetrospectiveStatus::Published, 'published_at' => now()]);

    // O poll da tabela é o que faz "Publicando" virar "Publicada" quando o job termina.
    $component
        ->call('$refresh')
        ->assertSee(RetrospectiveStatus::Published->getLabel());

    expect(RetrospectivesTable::configure(
        FilamentTable::make($component->instance())
    )->getPollingInterval())->not->toBeNull();
});
