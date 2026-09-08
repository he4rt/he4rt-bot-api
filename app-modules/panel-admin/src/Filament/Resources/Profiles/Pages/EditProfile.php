<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Profiles\Pages;

use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use He4rt\PanelAdmin\Filament\Resources\Profiles\ProfileResource;

class EditProfile extends EditRecord
{
    protected static string $resource = ProfileResource::class;

    /**
     * @return array<int, ViewAction>
     */
    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }
}
