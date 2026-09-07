<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Albums\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use He4rt\PanelAdmin\Filament\Resources\Albums\AlbumResource;

final class EditAlbum extends EditRecord
{
    protected static string $resource = AlbumResource::class;

    /**
     * @return DeleteAction[]
     */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
