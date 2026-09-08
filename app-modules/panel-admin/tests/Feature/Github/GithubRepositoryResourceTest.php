<?php

declare(strict_types=1);

use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use He4rt\Identity\User\Models\User;
use He4rt\IntegrationGithub\Backfill\Jobs\BackfillGithubRepository;
use He4rt\IntegrationGithub\Models\GithubRepository;
use He4rt\PanelAdmin\Github\Resources\GithubRepositoryResource\Pages\CreateGithubRepository;
use He4rt\PanelAdmin\Github\Resources\GithubRepositoryResource\Pages\ListGithubRepositories;
use Illuminate\Support\Facades\Queue;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user);

    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

test('admin cria um repositório', function (): void {
    livewire(CreateGithubRepository::class)
        ->fillForm(['full_name' => 'he4rt/heartdevs.com', 'enabled' => true])
        ->call('create')
        ->assertHasNoFormErrors();

    $repo = GithubRepository::query()->where('full_name', 'he4rt/heartdevs.com')->sole();

    expect($repo)->not->toBeNull();
});

test('rejeita full_name sem owner/repo', function (): void {
    livewire(CreateGithubRepository::class)
        ->fillForm(['full_name' => '4noobs'])
        ->call('create')
        ->assertHasFormErrors(['full_name']);
});

test('rejeita full_name duplicado', function (): void {
    GithubRepository::factory()->create(['full_name' => 'he4rt/4noobs']);

    livewire(CreateGithubRepository::class)
        ->fillForm(['full_name' => 'he4rt/4noobs'])
        ->call('create')
        ->assertHasFormErrors(['full_name']);
});

test('lista os repositórios cadastrados', function (): void {
    $mine = GithubRepository::factory()->create(['full_name' => 'he4rt/mine']);

    livewire(ListGithubRepositories::class)
        ->loadTable()
        ->assertCanSeeTableRecords([$mine]);
});

test('backfill pelo painel enfileira o job em segundo plano', function (): void {
    Queue::fake();

    $repo = GithubRepository::factory()->create(['full_name' => 'he4rt/a']);

    livewire(ListGithubRepositories::class)
        ->callAction(TestAction::make('backfill')->table($repo))
        ->assertNotified('Backfill enfileirado');

    Queue::assertPushed(
        BackfillGithubRepository::class,
        fn (BackfillGithubRepository $job): bool => $job->repository->is($repo),
    );
});
