<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\ContentEntries;

use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use He4rt\Contents\Models\ContentEntry;
use He4rt\PanelAdmin\Filament\Resources\ContentEntries\Pages\EditContentEntry;
use He4rt\PanelAdmin\Filament\Resources\ContentEntries\Pages\ListContentEntries;
use He4rt\PanelAdmin\Filament\Resources\ContentEntries\Pages\ViewContentEntry;
use He4rt\PanelAdmin\Filament\Resources\ContentEntries\Schemas\ContentEntryForm;
use He4rt\PanelAdmin\Filament\Resources\ContentEntries\Schemas\ContentEntryInfolist;
use He4rt\PanelAdmin\Filament\Resources\ContentEntries\Tables\ContentEntriesTable;
use He4rt\PanelAdmin\Filament\Resources\ContentEntries\Widgets\ContentEntryStatsWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ContentEntryResource extends Resource
{
    protected static ?string $model = ContentEntry::class;

    protected static ?string $slug = 'articles';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $modelLabel = 'artigo';

    protected static ?string $pluralModelLabel = 'artigos';

    /**
     * Artigo entra pelo `contents:sync-articles`, nunca à mão: um registro
     * criado no painel não teria par no provider e sumiria do acervo.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Apagar é ilusório: o próximo sync recria a entrada a partir do provider.
     */
    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return ContentEntryForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ContentEntryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContentEntriesTable::configure($table);
    }

    /**
     * @return array<int, class-string>
     */
    public static function getWidgets(): array
    {
        return [
            ContentEntryStatsWidget::class,
        ];
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListContentEntries::route('/'),
            'view' => ViewContentEntry::route('/{record}'),
            'edit' => EditContentEntry::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['author', 'contentable']);
    }

    /**
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'author_handle'];
    }
}
