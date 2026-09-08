<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\PanelAdmin\Moderation\Livewire\ModerationQueue;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->user = User::factory()->superAdmin()->create();

    $this->actingAs($this->user);

    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

test('queue page renders with cases ordered by priority', function (): void {
    $lowPriority = ModerationCase::factory()->create([
        'priority' => 10,
    ]);
    $highPriority = ModerationCase::factory()->create([
        'priority' => 90,
    ]);

    $component = livewire(ModerationQueue::class);

    $cases = $component->get('cases');
    expect($cases->first()->id)->toBe($highPriority->id);
    expect($cases->last()->id)->toBe($lowPriority->id);
});

test('queue auto-selects first case on mount', function (): void {
    $case = ModerationCase::factory()->create();

    $component = livewire(ModerationQueue::class);

    expect($component->get('selectedCaseId'))->toBe($case->id);
});

test('queue filters cases by status', function (): void {
    ModerationCase::factory()->create([
        'status' => 'pending',
    ]);
    ModerationCase::factory()->create([
        'status' => 'resolved',
        'resolved_at' => now(),
    ]);

    $component = livewire(ModerationQueue::class)
        ->set('statusFilter', 'pending');

    expect($component->get('cases'))->toHaveCount(1);

    $component->set('statusFilter', 'all');
    expect($component->get('cases'))->toHaveCount(2);
});

test('queue filters cases by platform', function (): void {
    ModerationCase::factory()->create([
        'source_platform' => 'discord',
    ]);
    ModerationCase::factory()->create([
        'source_platform' => 'twitch',
    ]);

    $component = livewire(ModerationQueue::class)
        ->set('statusFilter', 'all')
        ->set('platformFilter', 'discord');

    expect($component->get('cases'))->toHaveCount(1);
});

test('selecting a case updates selectedCaseId', function (): void {
    $case1 = ModerationCase::factory()->create();
    $case2 = ModerationCase::factory()->create();

    livewire(ModerationQueue::class)
        ->call('selectCase', $case2->id)
        ->assertSet('selectedCaseId', $case2->id);
});

test('changing filter resets selection to first case', function (): void {
    $pending = ModerationCase::factory()->create([
        'status' => 'pending',
        'priority' => 80,
    ]);
    ModerationCase::factory()->create([
        'status' => 'resolved',
        'resolved_at' => now(),
        'priority' => 90,
    ]);

    $component = livewire(ModerationQueue::class);

    expect($component->get('selectedCaseId'))->toBe($pending->id);
});

test('dismiss action updates case status', function (): void {
    $case = ModerationCase::factory()->create([
        'status' => 'pending',
    ]);

    livewire(ModerationQueue::class)
        ->call('selectCase', $case->id)
        ->callAction('dismiss')
        ->assertNotified();

    $case->refresh();
    expect($case->status->value)->toBe('dismissed');
    expect($case->resolved_at)->not->toBeNull();
});

test('escalate action updates case status', function (): void {
    $case = ModerationCase::factory()->create([
        'status' => 'pending',
    ]);

    livewire(ModerationQueue::class)
        ->call('selectCase', $case->id)
        ->callAction('escalate')
        ->assertNotified();

    $case->refresh();
    expect($case->status->value)->toBe('escalated');
});

test('empty state shows when no cases match filters', function (): void {
    ModerationCase::factory()->create([
        'source_platform' => 'discord',
    ]);

    $component = livewire(ModerationQueue::class)
        ->set('statusFilter', 'all')
        ->set('platformFilter', 'github');

    expect($component->get('cases'))->toBeEmpty();
    expect($component->get('selectedCaseId'))->toBeNull();
});
