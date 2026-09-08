<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Albums\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use He4rt\PanelAdmin\Filament\Resources\Albums\AlbumResource;

final class ListAlbums extends ListRecords
{
    protected static string $resource = AlbumResource::class;

    /**
     * @return CreateAction[]
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
