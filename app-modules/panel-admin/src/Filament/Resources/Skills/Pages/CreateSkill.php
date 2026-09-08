<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Skills\Pages;

use Filament\Resources\Pages\CreateRecord;
use He4rt\PanelAdmin\Filament\Resources\Skills\SkillResource;

class CreateSkill extends CreateRecord
{
    protected static string $resource = SkillResource::class;
}
