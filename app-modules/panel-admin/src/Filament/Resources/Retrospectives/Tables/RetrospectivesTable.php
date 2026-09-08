<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Retrospectives\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use He4rt\Community\Retrospective\Enums\RetrospectiveStatus;

class RetrospectivesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Publicar despacha um job; sem poll a linha fica em "Publicando" até o
            // operador recarregar na mão.
            ->poll('10s')
            ->columns([
                TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),

                TextColumn::make('since')
                    ->label('Início')
                    ->date('d/m/Y')
                    ->timezone(config('app.display_timezone'))
                    ->sortable(),

                TextColumn::make('until')
                    ->label('Fim')
                    ->date('d/m/Y')
                    ->timezone(config('app.display_timezone'))
                    ->sortable(),

                TextColumn::make('published_at')
                    ->label('Publicada em')
                    ->dateTime('d/m/Y H:i')
                    ->timezone(config('app.display_timezone'))
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(RetrospectiveStatus::class),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
