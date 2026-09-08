<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\ContentEntries\Tables;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use He4rt\Contents\Enums\ContentProvider;
use He4rt\Contents\Models\ContentEntry;
use Illuminate\Database\Eloquent\Builder;

class ContentEntriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->columns([
                ImageColumn::make('thumbnail_url')
                    ->label('Capa')
                    ->imageHeight(40)
                    ->imageWidth(72)
                    // A capa é remota (CDN do provider): sem isto o Filament
                    // faria uma requisição por linha só para saber se existe.
                    ->checkFileExistence(condition: false),

                TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->sortable()
                    ->limit(60)
                    ->weight(FontWeight::Medium),

                TextColumn::make('author.username')
                    ->label('Autor')
                    ->searchable()
                    ->placeholder('Não vinculado')
                    ->description(static fn (ContentEntry $record): string => $record->author_handle),

                TextColumn::make('provider')
                    ->label('Provider')
                    ->badge()
                    ->sortable(),

                TextColumn::make('published_at')
                    ->label('Publicado')
                    ->dateTime('d/m/Y H:i')
                    ->timezone(config('app.display_timezone'))
                    ->sortable(),

                TextColumn::make('reactions_count')
                    ->label('Reações')
                    ->numeric(0)
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('comments_count')
                    ->label('Comentários')
                    ->numeric(0)
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('metrics_synced_at')
                    ->label('Métricas de')
                    ->since()
                    ->sortable()
                    ->placeholder('Nunca')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('provider')
                    ->label('Provider')
                    ->options(ContentProvider::class),

                Filter::make('unlinked_author')
                    ->label('Sem autor vinculado')
                    ->query(static fn (Builder $query): Builder => $query->whereNull('author_id')),

                Filter::make('stale_metrics')
                    ->label('Métricas desatualizadas')
                    ->query(static fn (Builder $query): Builder => $query
                        ->where(static fn (Builder $inner): Builder => $inner
                            ->whereNull('metrics_synced_at')
                            ->orWhere('metrics_synced_at', '<', now()->subWeek()))),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('openSource')
                    ->label('Abrir no provider')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->url(static fn (ContentEntry $record): string => $record->url)
                    ->openUrlInNewTab(),
            ]);
    }
}
