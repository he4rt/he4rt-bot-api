<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Profiles\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use He4rt\Profile\Enums\SeniorityLevel;
use He4rt\Profile\Enums\StartAvailability;

/**
 * `expected_salary_min` e `expected_salary_max` ficam de fora: são dado
 * sensível, exibido apenas no infolist, em seção colapsada.
 */
class ProfileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Apresentação')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('nickname')
                            ->label('Apelido')
                            ->maxLength(255),

                        TextInput::make('headline')
                            ->label('Headline')
                            ->maxLength(255),

                        DatePicker::make('birthdate')
                            ->label('Nascimento')
                            ->maxDate(now())
                            ->native(condition: false)
                            ->displayFormat('d/m/Y'),

                        Textarea::make('about')
                            ->label('Sobre')
                            ->rows(5)
                            ->maxLength(5_000)
                            ->columnSpanFull(),
                    ]),

                Section::make('Carreira')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('seniority_level')
                            ->label('Senioridade')
                            ->options(SeniorityLevel::class),

                        TextInput::make('years_experience')
                            ->label('Anos de experiência')
                            ->integer()
                            ->minValue(0)
                            ->maxValue(70),

                        Toggle::make('available_for_proposals')
                            ->label('Aberto a propostas')
                            ->live(),

                        Select::make('start_availability')
                            ->label('Disponibilidade de início')
                            ->options(StartAvailability::class)
                            ->visible(static fn (Get $get): bool => (bool) $get('available_for_proposals')),
                    ]),
            ]);
    }
}
