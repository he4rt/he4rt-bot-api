<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Users\Pages;

use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use He4rt\PanelAdmin\Filament\Resources\Users\UserResource;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

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
