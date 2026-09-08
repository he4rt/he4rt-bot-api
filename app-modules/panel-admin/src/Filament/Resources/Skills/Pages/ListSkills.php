<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Skills\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use He4rt\PanelAdmin\Filament\Resources\Skills\SkillResource;

class ListSkills extends ListRecords
{
    protected static string $resource = SkillResource::class;

    /**
     * @return array<int, CreateAction>
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
