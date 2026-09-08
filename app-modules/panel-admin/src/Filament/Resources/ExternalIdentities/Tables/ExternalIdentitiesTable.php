<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\ExternalIdentities\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use He4rt\Identity\ExternalIdentity\Enums\CredentialsType;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use Illuminate\Database\Eloquent\Builder;

class ExternalIdentitiesTable
{
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('model_type')
                    ->label('Model Type'),

                TextColumn::make('model.name')
                    ->label('Owner')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHasMorph('model', '*', static function (Builder $q) use ($search): void {
                        $q->where('name', 'ilike', sprintf('%%%s%%', $search));
                    })),

                TextColumn::make('type')
                    ->badge()
                    ->label('Type'),

                TextColumn::make('provider')
                    ->badge()
                    ->label('Provider')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('external_account_id')
                    ->label('External Account Id'),

                TextColumn::make('connectedByUser.name')
                    ->label('Connected By')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('connected_at')
                    ->label('Connected Date')
                    ->dateTime('d/m/Y H:i')
                    ->timezone(config('app.display_timezone'))
                    ->sortable(),

                TextColumn::make('disconnected_at')
                    ->label('Disconnected Date')
                    ->dateTime('d/m/Y H:i')
                    ->timezone(config('app.display_timezone'))
                    ->placeholder('Ativa')
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('provider')
                    ->options(IdentityProvider::class)
                    ->multiple()
                    ->searchable(),

                SelectFilter::make('credentials_type')
                    ->options(CredentialsType::class),

                TernaryFilter::make('connection_state')
                    ->label('Conexão ativa')
                    ->queries(
                        true: static fn (Builder $query): Builder => $query->whereNull('disconnected_at'),
                        false: static fn (Builder $query): Builder => $query->whereNotNull('disconnected_at'),
                        blank: static fn (Builder $query): Builder => $query,
                    ),

                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }
}
