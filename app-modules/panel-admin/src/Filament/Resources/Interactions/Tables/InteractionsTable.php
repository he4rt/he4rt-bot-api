<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Interactions\Tables;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use He4rt\Activity\Tracking\Enums\ActivityType;
use He4rt\Activity\Tracking\Enums\AttributionMethod;
use He4rt\Activity\Tracking\Models\Interaction;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\PanelAdmin\Contributions\Actions\HideInteractionAction;
use He4rt\PanelAdmin\Contributions\Actions\UnhideInteractionAction;
use Illuminate\Database\Eloquent\Builder;

final class InteractionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('occurred_at', 'desc')
            // A coluna Contribuição lê a origem por linha: sem eager load é um N+1 por página.
            ->modifyQueryUsing(static fn (Builder $query): Builder => $query->with([
                'source',
                'user',
                'hiddenByUser',
                'externalIdentity',
            ]))
            ->columns([
                TextColumn::make('user.name')
                    ->label('Pessoa')
                    ->description(static fn (Interaction $record): ?string => $record->user->name === $record->user->username
                        ? null
                        : $record->user->username)
                    ->searchable(['users.name', 'users.username']),

                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->icon(static fn (Interaction $record): string => $record->externalIdentity->provider->getIcon()),

                TextColumn::make('source.contributionTitle')
                    ->label('Contribuição')
                    ->state(static fn (Interaction $record): string => $record->detail()?->contributionTitle() ?? '—')
                    ->description(static fn (Interaction $record): ?string => $record->detail()?->contributionContext())
                    ->url(static fn (Interaction $record): ?string => $record->detail()?->contributionUrl(), shouldOpenInNewTab: true)
                    ->limit(60)
                    ->wrap(),

                TextColumn::make('attributed_by')
                    ->label('Atribuição')
                    ->badge()
                    ->tooltip(static fn (AttributionMethod $state): string => $state->getDescription()),

                TextColumn::make('occurred_at')
                    ->label('Ocorrido em')
                    ->dateTime('d/m/Y H:i')
                    ->timezone(config('app.display_timezone'))
                    ->sortable(),

                IconColumn::make('hidden_at')
                    ->label('Visível')
                    ->boolean()
                    ->trueIcon('heroicon-o-eye-slash')
                    ->falseIcon('heroicon-o-eye')
                    ->trueColor('gray')
                    ->falseColor('success')
                    ->tooltip(static fn (Interaction $record): ?string => $record->isVisible()
                        ? null
                        : 'Oculta por '.($record->hiddenByUser->name ?? 'alguém')),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(ActivityType::class)
                    ->multiple(),

                SelectFilter::make('provider')
                    ->label('Origem')
                    ->options(IdentityProvider::class)
                    ->query(static fn (Builder $query, array $data): Builder => filled($data['values'] ?? [])
                        ? $query->whereHas(
                            'externalIdentity',
                            static fn (Builder $identity): Builder => $identity->whereIn('provider', $data['values']),
                        )
                        : $query)
                    ->multiple(),

                SelectFilter::make('attributed_by')
                    ->label('Atribuição')
                    ->options(AttributionMethod::class)
                    ->multiple(),

                Filter::make('hidden')
                    ->label('Somente ocultas')
                    ->query(static fn (Builder $query): Builder => $query->whereNotNull('hidden_at')),
            ])
            ->recordActions([
                HideInteractionAction::make(),
                UnhideInteractionAction::make(),
            ]);
    }
}
