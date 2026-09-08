<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Appeals\ModerationAppeal;
use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\Moderation\Enforcement\ModerationAction;
use He4rt\PanelAdmin\Moderation\Livewire\AppealQueue;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->user = User::factory()->superAdmin()->create();

    $this->actingAs($this->user);

    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

test('appeal queue renders with appeals ordered by sla_deadline', function (): void {
    $laterDeadline = ModerationAppeal::factory()->create([
        'sla_deadline' => now()->addHours(48),
    ]);
    $soonerDeadline = ModerationAppeal::factory()->create([
        'sla_deadline' => now()->addHours(12),
    ]);

    $component = livewire(AppealQueue::class);

    $appeals = $component->get('appeals');
    expect($appeals->first()->id)->toBe($soonerDeadline->id);
    expect($appeals->last()->id)->toBe($laterDeadline->id);
});

test('appeal queue auto-selects first appeal on mount', function (): void {
    $appeal = ModerationAppeal::factory()->create();

    $component = livewire(AppealQueue::class);

    expect($component->get('selectedAppealId'))->toBe($appeal->id);
});

test('appeal queue filters by status', function (): void {
    ModerationAppeal::factory()->create(['status' => 'pending']);
    ModerationAppeal::factory()->create([
        'status' => 'upheld',
        'reviewer_id' => User::factory(),
        'reviewer_notes' => 'test',
        'resolved_at' => now(),
    ]);

    $component = livewire(AppealQueue::class)
        ->set('statusFilter', 'pending');

    expect($component->get('appeals'))->toHaveCount(1);

    $component->set('statusFilter', 'all');
    expect($component->get('appeals'))->toHaveCount(2);
});

test('selecting an appeal updates selectedAppealId', function (): void {
    $appeal1 = ModerationAppeal::factory()->create();
    $appeal2 = ModerationAppeal::factory()->create();

    livewire(AppealQueue::class)
        ->call('selectAppeal', $appeal2->id)
        ->assertSet('selectedAppealId', $appeal2->id);
});

test('uphold action resolves appeal as upheld', function (): void {
    $moderator = User::factory()->create();
    $case = ModerationCase::factory()->create();
    $action = ModerationAction::factory()->create([
        'case_id' => $case->id,
        'moderator_id' => $moderator->id,
    ]);
    $appeal = ModerationAppeal::factory()->create([
        'action_id' => $action->id,
        'status' => 'pending',
    ]);

    livewire(AppealQueue::class)
        ->call('selectAppeal', $appeal->id)
        ->callAction('uphold', ['reviewer_notes' => 'Decision stands'])
        ->assertNotified();

    $appeal->refresh();
    expect($appeal->status->value)->toBe('upheld');
    expect($appeal->reviewer_notes)->toBe('Decision stands');
    expect($appeal->reviewer_id)->toBe($this->user->id);
});

test('overturn action resolves appeal as overturned', function (): void {
    $moderator = User::factory()->create();
    $case = ModerationCase::factory()->create();
    $action = ModerationAction::factory()->create([
        'case_id' => $case->id,
        'moderator_id' => $moderator->id,
    ]);
    $appeal = ModerationAppeal::factory()->create([
        'action_id' => $action->id,
        'status' => 'pending',
    ]);

    livewire(AppealQueue::class)
        ->call('selectAppeal', $appeal->id)
        ->callAction('overturn', ['reviewer_notes' => 'Overturning the ban'])
        ->assertNotified();

    $appeal->refresh();
    expect($appeal->status->value)->toBe('overturned');
    expect($appeal->reviewer_notes)->toBe('Overturning the ban');
});

test('empty state shows when no appeals match filter', function (): void {
    ModerationAppeal::factory()->create(['status' => 'pending']);

    $component = livewire(AppealQueue::class)
        ->set('statusFilter', 'upheld');

    expect($component->get('appeals'))->toBeEmpty();
    expect($component->get('selectedAppealId'))->toBeNull();
});
