<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Profiles\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use He4rt\PanelAdmin\Filament\Resources\Profiles\ProfileResource;

class ViewProfile extends ViewRecord
{
    protected static string $resource = ProfileResource::class;

    /**
     * @return array<int, EditAction>
     */
    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
