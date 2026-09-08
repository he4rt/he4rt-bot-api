<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Albums\Pages;

use Filament\Resources\Pages\CreateRecord;
use He4rt\PanelAdmin\Filament\Resources\Albums\AlbumResource;

final class CreateAlbum extends CreateRecord
{
    protected static string $resource = AlbumResource::class;
}
