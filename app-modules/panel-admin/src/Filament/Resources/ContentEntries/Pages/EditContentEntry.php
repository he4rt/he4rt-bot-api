<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\ContentEntries\Pages;

use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use He4rt\PanelAdmin\Filament\Resources\ContentEntries\ContentEntryResource;

class EditContentEntry extends EditRecord
{
    protected static string $resource = ContentEntryResource::class;

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
