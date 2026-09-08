<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Skills;

use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use He4rt\PanelAdmin\Filament\Resources\Skills\Pages\CreateSkill;
use He4rt\PanelAdmin\Filament\Resources\Skills\Pages\EditSkill;
use He4rt\PanelAdmin\Filament\Resources\Skills\Pages\ListSkills;
use He4rt\PanelAdmin\Filament\Resources\Skills\Schemas\SkillForm;
use He4rt\PanelAdmin\Filament\Resources\Skills\Tables\SkillsTable;
use He4rt\Profile\Models\Skill;
use Illuminate\Database\Eloquent\Builder;

class SkillResource extends Resource
{
    protected static ?string $model = Skill::class;

    protected static ?string $slug = 'skills';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return SkillForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SkillsTable::configure($table);
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListSkills::route('/'),
            'create' => CreateSkill::route('/create'),
            'edit' => EditSkill::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('profileSkills');
    }

    /**
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'slug'];
    }
}
