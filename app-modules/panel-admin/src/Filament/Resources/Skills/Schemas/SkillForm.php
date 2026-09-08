<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Skills\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use He4rt\Profile\Enums\SkillCategory;
use Illuminate\Support\Str;

class SkillForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(static fn (Set $set, ?string $state) => $set(
                        'slug',
                        Str::slug($state ?? ''),
                    )),

                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(),

                Select::make('category')
                    ->label('Categoria')
                    ->options(SkillCategory::class)
                    ->required(),

                TextInput::make('icon')
                    ->label('Ícone')
                    ->maxLength(255)
                    ->helperText('Nome do ícone, ex.: devicon-php-plain'),
            ]);
    }
}
