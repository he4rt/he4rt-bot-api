<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Profiles\Pages;

use Filament\Resources\Pages\ListRecords;
use He4rt\PanelAdmin\Filament\Resources\Profiles\ProfileResource;

class ListProfiles extends ListRecords
{
    protected static string $resource = ProfileResource::class;
}
