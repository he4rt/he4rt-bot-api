<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Events\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use He4rt\Events\CheckIn\Enums\CheckInMethod;
use He4rt\Events\Enrollment\Enums\AttendanceRequirement;
use He4rt\Events\Enrollment\Enums\EnrollmentMethod;
use He4rt\Events\Event\Enums\EventStatus;
use He4rt\Events\Event\Enums\EventType;
use He4rt\Events\Event\Models\Event;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;

final class EventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('title')
                    ->label(__('panel-admin::events.columns.title'))
                    ->required()
                    ->maxLength(200)
                    ->columnSpanFull(),

                TextInput::make('slug')
                    ->label(__('panel-admin::events.columns.slug'))
                    ->required()
                    ->maxLength(120)
                    ->unique(
                        table: Event::class,
                        column: 'slug',
                        ignoreRecord: true,
                    ),

                Select::make('event_type')
                    ->label(__('panel-admin::events.columns.type'))
                    ->options(EventType::class)
                    ->required(),

                TextInput::make('location')
                    ->label(__('panel-admin::events.columns.location'))
                    ->nullable(),

                Textarea::make('description')
                    ->label(__('panel-admin::events.columns.description'))
                    ->nullable()
                    ->columnSpanFull(),

                SpatieMediaLibraryFileUpload::make('cover')
                    ->label(__('panel-admin::events.columns.cover'))
                    ->collection('cover')
                    ->image()
                    ->imageEditor()
                    ->columnSpanFull(),

                DateTimePicker::make('starts_at')
                    ->label(__('panel-admin::events.columns.starts_at'))
                    ->required(),

                DateTimePicker::make('ends_at')
                    ->label(__('panel-admin::events.columns.ends_at'))
                    ->required()
                    ->after('starts_at'),

                Select::make('status')
                    ->label(__('panel-admin::events.columns.status'))
                    ->options(EventStatus::class)
                    ->default(EventStatus::Draft)
                    ->required()
                    ->columnSpanFull(),

                Section::make(__('panel-admin::events.sections.enrollment_policy'))
                    ->relationship('enrollmentPolicy')
                    ->columns(2)
                    ->schema([
                        Select::make('enrollment_method')
                            ->label(__('panel-admin::events.form.enrollment_method'))
                            ->options(EnrollmentMethod::class)
                            ->live()
                            ->required(),

                        Select::make('check_in_method')
                            ->label(__('panel-admin::events.form.check_in_method'))
                            ->options(CheckInMethod::class)
                            ->required(),

                        TextInput::make('capacity')
                            ->label(__('panel-admin::events.form.capacity'))
                            ->integer()
                            ->minValue(1)
                            ->nullable(),

                        Toggle::make('has_waitlist')
                            ->label(__('panel-admin::events.form.waitlist_enabled'))
                            ->default(state: false),

                        Select::make('attendance_requirement')
                            ->label(__('panel-admin::events.form.attendance_requirement'))
                            ->options(fn (Get $get): array => self::attendanceRequirementOptions($get))
                            ->live()
                            ->required(),

                        TextInput::make('minimum_days')
                            ->label(__('panel-admin::events.form.minimum_days'))
                            ->helperText(__('panel-admin::events.form.helpers.minimum_days'))
                            ->integer()
                            ->minValue(1)
                            ->maxValue(fn (Get $get): ?int => self::minimumDaysMaxValue($get))
                            ->default(1)
                            ->required(fn (Get $get): bool => $get('attendance_requirement') === AttendanceRequirement::MinimumDays->value)
                            ->visible(fn (Get $get): bool => $get('attendance_requirement') === AttendanceRequirement::MinimumDays->value),

                        TextInput::make('cancellation_deadline_hours')
                            ->label(__('panel-admin::events.form.cancellation_deadline_hours'))
                            ->integer()
                            ->minValue(0)
                            ->nullable(),

                        TextInput::make('xp_on_confirmed')
                            ->label(__('panel-admin::events.form.xp_on_confirmed'))
                            ->integer()
                            ->minValue(0)
                            ->default(0),

                        TextInput::make('xp_on_checked_in')
                            ->label(__('panel-admin::events.form.xp_on_checked_in'))
                            ->integer()
                            ->minValue(0)
                            ->default(0),

                        TextInput::make('xp_on_attended')
                            ->label(__('panel-admin::events.form.xp_on_attended'))
                            ->integer()
                            ->minValue(0)
                            ->default(0),

                        Repeater::make('application_schema')
                            ->label('Application Form Questions')
                            ->nullable()
                            ->columnSpanFull()
                            ->visible(fn (Get $get): bool => $get('enrollment_method') === EnrollmentMethod::Application)
                            ->addActionLabel('Add question')
                            ->reorderable()
                            ->collapsible()
                            ->schema([
                                Hidden::make('key')
                                    ->default(fn (): string => (string) Str::uuid()),
                                Select::make('type')
                                    ->label('Type')
                                    ->options([
                                        'text' => 'Short text',
                                        'textarea' => 'Long text',
                                        'select' => 'Single choice',
                                        'checkbox' => 'Multiple choice',
                                    ])
                                    ->required()
                                    ->live(),
                                TextInput::make('label')
                                    ->label('Question')
                                    ->required()
                                    ->maxLength(255),
                                Toggle::make('required')
                                    ->label('Required')
                                    ->default(state: false),
                                TagsInput::make('options')
                                    ->label('Options')
                                    ->placeholder('Type an option and press Enter')
                                    ->visible(fn (Get $get): bool => in_array($get('type'), ['select', 'checkbox'], strict: true))
                                    ->required(fn (Get $get): bool => in_array($get('type'), ['select', 'checkbox'], strict: true)),
                            ]),
                    ]),
            ]);
    }

    /**
     * @return array<string, string>
     */
    private static function attendanceRequirementOptions(Get $get): array
    {
        $all = [
            AttendanceRequirement::AllDays->value => __('events::enums.attendance_requirement.all_days'),
            AttendanceRequirement::AnyDay->value => __('events::enums.attendance_requirement.any_day'),
            AttendanceRequirement::MinimumDays->value => __('events::enums.attendance_requirement.minimum_days'),
        ];

        $eventDays = self::eventDays($get);

        if ($eventDays === null) {
            return $all;
        }

        if ($eventDays === 1) {
            return [AttendanceRequirement::AnyDay->value => $all[AttendanceRequirement::AnyDay->value]];
        }

        return $all;
    }

    private static function minimumDaysMaxValue(Get $get): ?int
    {
        return self::eventDays($get);
    }

    private static function eventDays(Get $get): ?int
    {
        $startsAt = $get('../starts_at');
        $endsAt = $get('../ends_at');

        if ($startsAt === null || $endsAt === null) {
            return null;
        }

        $startsDay = Date::parse($startsAt)->startOfDay();
        $endsDay = Date::parse($endsAt)->startOfDay();

        return (int) $startsDay->diffInDays($endsDay) + 1;
    }
}
