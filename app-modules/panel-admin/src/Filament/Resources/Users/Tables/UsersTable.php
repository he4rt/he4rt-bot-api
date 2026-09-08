<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Users\Tables;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use He4rt\Identity\Authorization\Enums\UserRole;
use He4rt\Identity\User\Enums\UserSituation;
use He4rt\Identity\User\Models\User;
use He4rt\PanelAdmin\Moderation\Resources\ModerationCaseResource;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('username')
                    ->label('Username')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium),

                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->description(fn (User $record): ?string => $record->email),

                TextColumn::make('situation')
                    ->label('Situação')
                    ->badge()
                    ->state(fn (User $record): UserSituation => $record->situation),

                TextColumn::make('roles.name')
                    ->label('Papéis')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => UserRole::from($state)->getLabel())
                    ->color(fn (string $state): array => UserRole::from($state)->getColor())
                    ->placeholder('—'),

                TextColumn::make('suspended_until')
                    ->label('Suspenso até')
                    ->dateTime('d/m/Y H:i')
                    ->timezone(config('app.display_timezone'))
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_donator')
                    ->label('Apoiador')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('providers_count')
                    ->label('Identidades')
                    ->state(static fn (User $record): int => $record->providers->count())
                    ->numeric(0),

                TextColumn::make('first_login_at')
                    ->label('Primeiro login')
                    ->dateTime('d/m/Y H:i')
                    ->timezone(config('app.display_timezone'))
                    ->sortable()
                    ->placeholder('Nunca')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->timezone(config('app.display_timezone'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('situation')
                    ->label('Situação')
                    ->options(UserSituation::class)
                    ->query(static fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        UserSituation::Banned->value => $query->whereNotNull('banned_at'),
                        UserSituation::Suspended->value => $query
                            ->whereNull('banned_at')
                            ->where('suspended_until', '>', now()),
                        UserSituation::Active->value => $query
                            ->whereNull('banned_at')
                            ->where(static fn (Builder $inner): Builder => $inner
                                ->whereNull('suspended_until')
                                ->orWhere('suspended_until', '<=', now())),
                        default => $query,
                    }),

                SelectFilter::make('roles')
                    ->label('Papel')
                    ->relationship('roles', 'name')
                    ->getOptionLabelFromRecordUsing(fn (Role $record): string => UserRole::from($record->name)->getLabel()),

                TernaryFilter::make('is_donator')
                    ->label('Apoiador'),

                Filter::make('never_logged_in')
                    ->label('Nunca logou')
                    ->query(static fn (Builder $query): Builder => $query->whereNull('first_login_at')),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('moderationCases')
                    ->label('Casos de moderação')
                    ->icon(Heroicon::OutlinedShieldExclamation)
                    ->color('gray')
                    ->url(static fn (User $record): string => ModerationCaseResource::getUrl('index', [
                        'tableFilters' => [
                            'author' => ['value' => $record->getKey()],
                        ],
                    ])),
            ]);
    }
}
