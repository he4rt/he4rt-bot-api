<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Skills\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use He4rt\Profile\Enums\SkillCategory;
use He4rt\Profile\Models\Skill;
use Illuminate\Database\Eloquent\Builder;

class SkillsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->defaultGroup('category')
            ->groups([
                Group::make('category')
                    ->label('Categoria')
                    ->collapsible(),
            ])
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->color('gray')
                    ->copyable(),

                TextColumn::make('category')
                    ->label('Categoria')
                    ->badge()
                    ->sortable(),

                TextColumn::make('profile_skills_count')
                    ->label('Perfis')
                    ->counts('profileSkills')
                    ->numeric(0)
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Categoria')
                    ->options(SkillCategory::class)
                    ->multiple(),

                Filter::make('unused')
                    ->label('Sem nenhum perfil')
                    ->query(static fn (Builder $query): Builder => $query->whereDoesntHave('profileSkills')),
            ])
            ->recordActions([
                EditAction::make(),
                // Apagar skill em uso deixaria `profile_skills` órfão: não há cascade.
                DeleteAction::make()
                    ->visible(static fn (Skill $record): bool => $record->profileSkills()->doesntExist()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
