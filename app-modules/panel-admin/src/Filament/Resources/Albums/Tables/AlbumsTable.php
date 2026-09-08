<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Albums\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use He4rt\Events\Gallery\Enums\PhotoConversion;
use He4rt\Events\Gallery\Models\Album;
use Illuminate\Database\Eloquent\Builder;

final class AlbumsTable
{
    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('happened_at', 'desc')
            ->columns([
                SpatieMediaLibraryImageColumn::make('photos')
                    ->label(__('panel-admin::albums.columns.photos'))
                    ->collection(Album::PHOTOS)
                    ->conversion(PhotoConversion::Thumb->value)
                    ->stacked()
                    ->limit(3)
                    ->limitedRemainingText(),

                TextColumn::make('title')
                    ->label(__('panel-admin::albums.columns.title'))
                    ->description(fn (Album $record): ?string => $record->location)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('event.title')
                    ->label(__('panel-admin::albums.columns.event'))
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('happened_at')
                    ->label(__('panel-admin::albums.columns.happened_at'))
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('photos_count')
                    ->label(__('panel-admin::albums.columns.photos_count'))
                    ->counts('photos')
                    ->badge(),

                TextColumn::make('published_at')
                    ->label(__('panel-admin::albums.columns.published_at'))
                    ->dateTime('d/m/Y H:i')
                    ->timezone(config()->string('app.display_timezone'))
                    ->placeholder(__('panel-admin::albums.filters.drafts'))
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__('panel-admin::albums.columns.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('published')
                    ->label(__('panel-admin::albums.columns.published_at'))
                    ->queries(
                        true: self::onlyPublished(...),
                        false: self::onlyDrafts(...),
                    )
                    ->trueLabel(__('panel-admin::albums.filters.published'))
                    ->falseLabel(__('panel-admin::albums.filters.drafts')),
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

    /**
     * Mesma regra de `Album::scopePublished()`: um agendado para o futuro ainda
     * não está publicado.
     *
     * @param  Builder<Album>  $query
     * @return Builder<Album>
     */
    private static function onlyPublished(Builder $query): Builder
    {
        return $query->published();
    }

    /**
     * @param  Builder<Album>  $query
     * @return Builder<Album>
     */
    private static function onlyDrafts(Builder $query): Builder
    {
        return $query->whereNull('published_at');
    }
}
