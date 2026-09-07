<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Albums\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use He4rt\Events\Gallery\Actions\CuratePhoto;
use He4rt\Events\Gallery\DTOs\CuratePhotoData;
use He4rt\Events\Gallery\DTOs\Photo;
use He4rt\Events\Gallery\Enums\PhotoConversion;
use He4rt\Events\Gallery\Models\Album;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Curadoria das fotos de um álbum: destaque, legenda e ordem. O upload em si
 * acontece no formulário do álbum.
 */
final class PhotosRelationManager extends RelationManager
{
    protected static string $relationship = 'media';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('panel-admin::albums.photos.title');
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('collection_name', Album::PHOTOS))
            ->defaultSort('order_column')
            ->reorderable('order_column')
            ->paginated(condition: false)
            ->emptyStateHeading(__('panel-admin::albums.photos.empty'))
            ->columns([
                ImageColumn::make('preview')
                    ->label(__('panel-admin::albums.photos.columns.preview'))
                    ->state(fn (Media $record): string => PhotoConversion::Thumb->urlFor($record))
                    ->imageHeight(64)
                    ->square(),

                ToggleColumn::make('highlight')
                    ->label(__('panel-admin::albums.photos.columns.highlight'))
                    ->state(fn (Media $record): bool => Photo::fromMedia($record)->highlight)
                    ->updateStateUsing(function (Media $record, bool $state): void {
                        resolve(CuratePhoto::class)->handle($record, new CuratePhotoData(
                            caption: Photo::fromMedia($record)->caption,
                            highlight: $state,
                        ));
                    }),

                TextInputColumn::make('caption')
                    ->label(__('panel-admin::albums.photos.columns.caption'))
                    ->state(fn (Media $record): ?string => Photo::fromMedia($record)->caption)
                    ->rules(['nullable', 'string', 'max:140'])
                    ->updateStateUsing(function (Media $record, ?string $state): void {
                        resolve(CuratePhoto::class)->handle($record, new CuratePhotoData(
                            caption: $state,
                            highlight: Photo::fromMedia($record)->highlight,
                        ));
                    }),

                TextColumn::make('size')
                    ->label(__('panel-admin::albums.photos.columns.size'))
                    ->state(fn (Media $record): string => $record->human_readable_size),

                TextColumn::make('created_at')
                    ->label(__('panel-admin::albums.photos.columns.uploaded_at'))
                    ->dateTime('d/m H:i')
                    ->timezone(config()->string('app.display_timezone')),
            ])
            ->recordActions([
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
