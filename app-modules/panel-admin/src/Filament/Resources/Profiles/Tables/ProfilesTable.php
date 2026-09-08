<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Profiles\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use He4rt\Profile\Enums\SeniorityLevel;
use Illuminate\Database\Eloquent\Builder;

class ProfilesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('user.username')
                    ->label('Usuário')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium),

                TextColumn::make('nickname')
                    ->label('Apelido')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('headline')
                    ->label('Headline')
                    ->searchable()
                    ->limit(50)
                    ->placeholder('Não preenchido'),

                TextColumn::make('seniority_level')
                    ->label('Senioridade')
                    ->badge()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('years_experience')
                    ->label('Experiência')
                    ->numeric(0)
                    ->suffix(' anos')
                    ->sortable()
                    ->placeholder('—'),

                IconColumn::make('available_for_proposals')
                    ->label('Aberto a propostas')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('profile_skills_count')
                    ->label('Skills')
                    ->counts('profileSkills')
                    ->numeric(0)
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('seniority_level')
                    ->label('Senioridade')
                    ->options(SeniorityLevel::class)
                    ->multiple(),

                TernaryFilter::make('available_for_proposals')
                    ->label('Aberto a propostas'),

                Filter::make('incomplete')
                    ->label('Perfil incompleto')
                    ->query(static fn (Builder $query): Builder => $query
                        ->whereNull('headline')
                        ->orWhereNull('seniority_level')),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
