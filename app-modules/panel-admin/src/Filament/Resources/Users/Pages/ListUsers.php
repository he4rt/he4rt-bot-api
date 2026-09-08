<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Users\Pages;

use Filament\Resources\Pages\ListRecords;
use He4rt\PanelAdmin\Filament\Resources\Users\UserResource;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;
}
