<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use He4rt\Events\CheckIn\Enums\CheckInMethod;
use He4rt\Events\CheckIn\Models\CheckIn;
use He4rt\Events\CheckIn\Models\CheckInCode;
use He4rt\Events\Enrollment\Enums\AttendanceRequirement;
use He4rt\Events\Enrollment\Enums\EnrollmentMethod;
use He4rt\Events\Enrollment\Enums\EnrollmentStatus;
use He4rt\Events\Enrollment\Models\Enrollment;
use He4rt\Events\Enrollment\Models\EnrollmentPolicy;
use He4rt\Events\Event\Enums\EventType;
use He4rt\Events\Event\Models\Event;
use He4rt\Identity\User\Models\User;
use He4rt\PanelAdmin\Filament\Resources\Events\EventResource;
use He4rt\PanelAdmin\Filament\Resources\Events\Pages\CreateEvent;
use He4rt\PanelAdmin\Filament\Resources\Events\Pages\EditEvent;
use He4rt\PanelAdmin\Filament\Resources\Events\Pages\ListEvents;
use He4rt\PanelAdmin\Filament\Resources\Events\RelationManagers\CheckInCodesRelationManager;
use He4rt\PanelAdmin\Filament\Resources\Events\RelationManagers\EnrollmentsRelationManager;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $admin = User::factory()->superAdmin()->create(['username' => 'events-test-admin']);
    $this->actingAs($admin);

    Filament::setCurrentPanel(Filament::getPanel('admin'));

});

test('when visiting the events list page, then it renders successfully', function (): void {
    livewire(ListEvents::class)
        ->assertSuccessful();
});

test('when an event exists, then it appears in the events list', function (): void {
    $event = Event::factory()->create(['title' => 'He4rt Meetup #42']);

    livewire(ListEvents::class)
        ->loadTable()
        ->assertCanSeeTableRecords([$event]);
});

test('when visiting the create event page, then it renders successfully', function (): void {
    livewire(CreateEvent::class)
        ->assertSuccessful();
});

test('when visiting the edit event page, then it renders successfully', function (): void {
    $event = Event::factory()->create();

    livewire(EditEvent::class, ['record' => $event->getRouteKey()])
        ->assertSuccessful();
});

test('when checking the event resource model, then it points to Event', function (): void {
    expect(EventResource::getModel())->toBe(Event::class);
});

test('when submitting the create form with valid data, then event and enrollment policy are persisted', function (): void {
    $startsAt = now()->addDay();

    livewire(CreateEvent::class)
        ->fillForm([
            'title' => 'He4rt Meetup #42',
            'slug' => 'he4rt-meetup-42',
            'event_type' => EventType::Meetup,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->clone()->addHours(3),
            'enrollmentPolicy' => [
                'enrollment_method' => EnrollmentMethod::Rsvp,
                'check_in_method' => CheckInMethod::Manual,
                'attendance_requirement' => AttendanceRequirement::AnyDay,
                'xp_on_confirmed' => 0,
                'xp_on_checked_in' => 0,
                'xp_on_attended' => 0,
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $event = Event::query()->where('slug', 'he4rt-meetup-42')->first();
    expect($event)->not->toBeNull()
        ->and($event->title)->toBe('He4rt Meetup #42')
        ->and($event->enrollmentPolicy)->not->toBeNull()
        ->and($event->enrollmentPolicy->enrollment_method)->toBe(EnrollmentMethod::Rsvp);
});

test('when creating a same-day event, then attendance requirement options only allow any day', function (): void {
    $startsAt = now()->addDay()->setTime(9, 0);

    livewire(CreateEvent::class)
        ->fillForm([
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->clone()->setTime(18, 0),
        ])
        ->assertFormFieldExists(
            'enrollmentPolicy.attendance_requirement',
            fn (Select $field): bool => array_keys($field->getOptions()) === [AttendanceRequirement::AnyDay->value],
        );
});

test('when creating a multi-day event, then minimum days cannot exceed event days', function (): void {
    $startsAt = now()->addDay()->setTime(9, 0);

    livewire(CreateEvent::class)
        ->fillForm([
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->clone()->addDays(2)->setTime(18, 0),
            'enrollmentPolicy' => [
                'attendance_requirement' => AttendanceRequirement::MinimumDays,
            ],
        ])
        ->assertFormFieldExists(
            'enrollmentPolicy.minimum_days',
            fn (TextInput $field): bool => $field->getMaxValue() === 3,
        );
});

test('when submitting the create form with a duplicate slug, then validation fails', function (): void {
    $startsAt = now()->addDay();

    Event::factory()->create(['slug' => 'duplicate-slug']);

    livewire(CreateEvent::class)
        ->fillForm([
            'title' => 'Another Event',
            'slug' => 'duplicate-slug',
            'event_type' => EventType::Meetup,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->clone()->addHours(3),
            'enrollmentPolicy' => [
                'enrollment_method' => EnrollmentMethod::Rsvp,
                'check_in_method' => CheckInMethod::Manual,
                'attendance_requirement' => AttendanceRequirement::AnyDay,
                'xp_on_confirmed' => 0,
                'xp_on_checked_in' => 0,
                'xp_on_attended' => 0,
            ],
        ])
        ->call('create')
        ->assertHasFormErrors(['slug' => 'unique']);
});

test('when submitting the edit form with a new title, then it is updated in the database', function (): void {
    $event = Event::factory()
        ->has(EnrollmentPolicy::factory()->state([
            'attendance_requirement' => AttendanceRequirement::AnyDay,
            'minimum_days' => null,
        ]), 'enrollmentPolicy')
        ->create(['title' => 'Old Title']);

    livewire(EditEvent::class, ['record' => $event->getRouteKey()])
        ->fillForm(['title' => 'New Title'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($event->fresh()->title)->toBe('New Title');
});

test('when visiting the enrollments relation manager, then it renders successfully', function (): void {
    $event = Event::factory()->create();

    livewire(EnrollmentsRelationManager::class, [
        'ownerRecord' => $event,
        'pageClass' => EditEvent::class,
    ])
        ->assertSuccessful();
});

test('when admin generates a check-in code, then persisted code matches preview value', function (): void {
    $startsAt = now()->setTime(9, 0);
    $event = Event::factory()->create([
        'starts_at' => $startsAt,
        'ends_at' => $startsAt->clone()->setTime(18, 0),
    ]);

    livewire(CheckInCodesRelationManager::class, [
        'ownerRecord' => $event,
        'pageClass' => EditEvent::class,
    ])
        ->callTableAction('generateCode', data: [
            'digits' => '4',
            'code_preview' => '1234',
            'event_date' => now()->toDateString(),
            'starts_at' => now(),
            'expires_at' => now()->addHours(2),
            'max_uses' => '',
        ])
        ->assertHasNoTableActionErrors();

    expect(CheckInCode::query()->where('event_id', $event->id)->sole()->code)->toBe('1234');
});

test('when admin checks in an enrollment from relation manager, then participant is checked in', function (): void {
    $event = Event::factory()->create([
        'starts_at' => now()->setTime(9, 0),
        'ends_at' => now()->setTime(18, 0),
    ]);

    $enrollment = Enrollment::factory()->create([
        'event_id' => $event->id,
        'status' => EnrollmentStatus::Confirmed,
        'confirmed_at' => now(),
    ]);

    livewire(EnrollmentsRelationManager::class, [
        'ownerRecord' => $event,
        'pageClass' => EditEvent::class,
    ])
        ->callTableAction('checkIn', $enrollment, data: [
            'event_date' => now()->toDateString(),
        ])
        ->assertHasNoTableActionErrors();

    expect($enrollment->fresh()->status)->toBe(EnrollmentStatus::CheckedIn)
        ->and(CheckIn::query()->where('enrollment_id', $enrollment->id)->count())->toBe(1);
});

test('when admin bulk checks in selected enrollments, then all selected participants are checked in', function (): void {
    $event = Event::factory()->create([
        'starts_at' => now()->setTime(9, 0),
        'ends_at' => now()->setTime(18, 0),
    ]);

    $enrollments = Enrollment::factory()
        ->count(2)
        ->create([
            'event_id' => $event->id,
            'status' => EnrollmentStatus::Confirmed,
            'confirmed_at' => now(),
        ]);

    livewire(EnrollmentsRelationManager::class, [
        'ownerRecord' => $event,
        'pageClass' => EditEvent::class,
    ])
        ->callTableBulkAction('checkInSelected', $enrollments, data: [
            'event_date' => now()->toDateString(),
        ])
        ->assertHasNoTableBulkActionErrors();

    expect(Enrollment::query()->whereKey($enrollments->pluck('id'))->where('status', EnrollmentStatus::CheckedIn)->count())->toBe(2)
        ->and(CheckIn::query()->whereIn('enrollment_id', $enrollments->pluck('id'))->count())->toBe(2);
});

test('when enrollment has check-ins, then relation manager shows check-in history', function (): void {
    $startsAt = now()->setTime(9, 0);
    $event = Event::factory()->create([
        'starts_at' => $startsAt,
        'ends_at' => $startsAt->clone()->addDay()->setTime(18, 0),
    ]);

    $enrollment = Enrollment::factory()->create([
        'event_id' => $event->id,
        'status' => EnrollmentStatus::CheckedIn,
    ]);

    CheckIn::factory()->create([
        'enrollment_id' => $enrollment->id,
        'event_date' => $startsAt->toDateString(),
        'method' => CheckInMethod::Manual,
    ]);

    CheckIn::factory()->create([
        'enrollment_id' => $enrollment->id,
        'event_date' => $startsAt->clone()->addDay()->toDateString(),
        'method' => CheckInMethod::Manual,
    ]);

    livewire(EnrollmentsRelationManager::class, [
        'ownerRecord' => $event,
        'pageClass' => EditEvent::class,
    ])
        ->loadTable()
        ->assertSee($startsAt->toDateString())
        ->assertSee($startsAt->clone()->addDay()->toDateString());
});
