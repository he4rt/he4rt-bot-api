<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Resources;

use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\Moderation\Enums\CaseStatus;
use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Enums\Severity;
use He4rt\Moderation\Enums\ViolationType;
use He4rt\PanelAdmin\Moderation\ModerationCluster;
use He4rt\PanelAdmin\Moderation\Resources\ModerationCaseResource\Pages\ListModerationCases;
use He4rt\PanelAdmin\Moderation\Resources\ModerationCaseResource\Pages\ViewModerationCase;

class ModerationCaseResource extends Resource
{
    protected static ?string $cluster = ModerationCluster::class;

    protected static ?string $model = ModerationCase::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'cases';

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::moderation.navigation.cases');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('panel-admin::moderation.navigation.group_moderation');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('priority', 'desc')
            ->columns([
                TextColumn::make('priority')
                    ->badge()
                    ->color(fn (int $state) => match (true) {
                        $state >= 90 => 'danger',
                        $state >= 70 => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('violation_type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('source_platform')
                    ->badge()
                    ->sortable(),
                TextColumn::make('author.name')
                    ->label('Author')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (CaseStatus $state) => match ($state) {
                        CaseStatus::Pending => 'warning',
                        CaseStatus::Assigned => 'info',
                        CaseStatus::Resolved => 'success',
                        CaseStatus::Escalated => 'danger',
                        CaseStatus::Dismissed => 'gray',
                    }),
                TextColumn::make('severity')
                    ->badge(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(CaseStatus::class),
                SelectFilter::make('source_platform')
                    ->options(Platform::class),
                SelectFilter::make('violation_type')
                    ->options(ViolationType::class),
                SelectFilter::make('severity')
                    ->options(Severity::class),
                SelectFilter::make('author')
                    ->relationship('author', 'username')
                    ->searchable(),
            ]);
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListModerationCases::route('/'),
            'view' => ViewModerationCase::route('/{record}'),
        ];
    }
}
