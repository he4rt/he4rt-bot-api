<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Profiles\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use He4rt\Profile\Enums\SkillProficiency;
use He4rt\Profile\Models\Skill;

class ProfileSkillsRelationManager extends RelationManager
{
    protected static string $relationship = 'profileSkills';

    protected static ?string $title = 'Skills';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('skill_id')
                    ->label('Skill')
                    ->relationship('skill', 'name')
                    ->searchable()
                    ->getSearchResultsUsing(static fn (string $search): array => Skill::search($search))
                    ->required(),

                Select::make('proficiency')
                    ->label('Proficiência')
                    ->options(SkillProficiency::class)
                    ->required(),

                TextInput::make('years_experience')
                    ->label('Anos de experiência')
                    ->integer()
                    ->minValue(0)
                    ->maxValue(70),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('skill.name')
            ->columns([
                TextColumn::make('skill.name')
                    ->label('Skill')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('skill.category')
                    ->label('Categoria')
                    ->badge(),

                TextColumn::make('proficiency')
                    ->label('Proficiência')
                    ->badge()
                    ->sortable(),

                TextColumn::make('years_experience')
                    ->label('Experiência')
                    ->numeric(0)
                    ->suffix(' anos')
                    ->placeholder('—'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
