<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Retrospectives\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Formulário de CRIAÇÃO de uma edição: título e recorte, nada mais. Criar uma
 * retrospectiva não é montar deck — a curadoria inteira vive no Deck Builder
 * (ADR-0002), e duas telas escrevendo deck_config seriam duas fontes de verdade.
 */
class NewRetrospectiveForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Nova edição')
                    ->description('Depois de criar, o builder abre para montar o deck.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('Título')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        DateTimePicker::make('since')
                            ->label('Início do período')
                            ->seconds(condition: false)
                            ->timezone(config('app.display_timezone'))
                            ->required(),

                        DateTimePicker::make('until')
                            ->label('Fim do período')
                            ->seconds(condition: false)
                            ->timezone(config('app.display_timezone'))
                            ->required(),
                    ]),
            ]);
    }
}
