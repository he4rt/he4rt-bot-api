<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Albums;

use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use He4rt\Events\Gallery\Models\Album;
use He4rt\PanelAdmin\Filament\Resources\Albums\Pages\CreateAlbum;
use He4rt\PanelAdmin\Filament\Resources\Albums\Pages\EditAlbum;
use He4rt\PanelAdmin\Filament\Resources\Albums\Pages\ListAlbums;
use He4rt\PanelAdmin\Filament\Resources\Albums\RelationManagers\PhotosRelationManager;
use He4rt\PanelAdmin\Filament\Resources\Albums\Schemas\AlbumForm;
use He4rt\PanelAdmin\Filament\Resources\Albums\Tables\AlbumsTable;

final class AlbumResource extends Resource
{
    protected static ?string $model = Album::class;

    protected static ?string $slug = 'albums';

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::albums.plural');
    }

    public static function getModelLabel(): string
    {
        return __('panel-admin::albums.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('panel-admin::albums.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return AlbumForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AlbumsTable::table($table);
    }

    public static function getRelations(): array
    {
        return [
            PhotosRelationManager::class,
        ];
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListAlbums::route('/'),
            'create' => CreateAlbum::route('/create'),
            'edit' => EditAlbum::route('/{record}/edit'),
        ];
    }
}
