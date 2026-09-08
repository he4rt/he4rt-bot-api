<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Marketing\Resources\ShortLinks;

use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use He4rt\Marketing\ShortLink\Models\ShortLink;
use He4rt\PanelAdmin\Marketing\MarketingCluster;
use He4rt\PanelAdmin\Marketing\Resources\ShortLinks\Pages\CreateShortLink;
use He4rt\PanelAdmin\Marketing\Resources\ShortLinks\Pages\EditShortLink;
use He4rt\PanelAdmin\Marketing\Resources\ShortLinks\Pages\ListShortLinks;
use He4rt\PanelAdmin\Marketing\Resources\ShortLinks\Pages\ViewShortLink;
use He4rt\PanelAdmin\Marketing\Resources\ShortLinks\Schemas\ShortLinkForm;
use He4rt\PanelAdmin\Marketing\Resources\ShortLinks\Schemas\ShortLinkInfolist;
use He4rt\PanelAdmin\Marketing\Resources\ShortLinks\Tables\ShortLinksTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ShortLinkResource extends Resource
{
    protected static ?string $model = ShortLink::class;

    protected static ?string $cluster = MarketingCluster::class;

    protected static ?string $slug = 'short-links';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'slug';

    /**
     * The public URL of a short link.
     *
     * The named route lives in the `portal` module. Using it here makes the
     * panel follow any prefix or domain change, and fails loudly if the public
     * edge is removed.
     */
    public static function shortUrl(ShortLink|string $link): string
    {
        return route('short-link.redirect', [
            'slug' => $link instanceof ShortLink ? $link->slug : $link,
        ]);
    }

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::marketing.navigation.short_links');
    }

    public static function getModelLabel(): string
    {
        return __('panel-admin::marketing.short_links.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('panel-admin::marketing.short_links.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return ShortLinkForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ShortLinkInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ShortLinksTable::table($table);
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListShortLinks::route('/'),
            'create' => CreateShortLink::route('/create'),
            'view' => ViewShortLink::route('/{record}'),
            'edit' => EditShortLink::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['slug', 'base_slug', 'destination_url'];
    }

    /**
     * A slug stays reserved after a soft delete, so the route must find the
     * deleted record. Without this, edit and restore answer 404.
     *
     * @return Builder<ShortLink>
     */
    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        /** @var Builder<ShortLink> $query */
        $query = parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);

        return $query;
    }
}
