<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Profiles\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ProfileInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Apresentação')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('user.username')
                            ->label('Usuário'),

                        TextEntry::make('nickname')
                            ->label('Apelido')
                            ->placeholder('—'),

                        TextEntry::make('headline')
                            ->label('Headline')
                            ->placeholder('Não preenchido'),

                        TextEntry::make('birthdate')
                            ->label('Nascimento')
                            ->date('d/m/Y')
                            ->placeholder('—'),

                        TextEntry::make('about')
                            ->label('Sobre')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ]),

                Section::make('Carreira')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('seniority_level')
                            ->label('Senioridade')
                            ->badge()
                            ->placeholder('—'),

                        TextEntry::make('years_experience')
                            ->label('Experiência')
                            ->numeric(0)
                            ->suffix(' anos')
                            ->placeholder('—'),

                        IconEntry::make('available_for_proposals')
                            ->label('Aberto a propostas')
                            ->boolean(),

                        TextEntry::make('start_availability')
                            ->label('Disponibilidade de início')
                            ->badge()
                            ->placeholder('—'),
                    ]),

                Section::make('Remuneração pretendida')
                    ->icon(Heroicon::OutlinedLockClosed)
                    ->description('Informado pela pessoa. Não exibir fora do painel.')
                    ->collapsible()
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('expected_salary_min')
                            ->label('Mínimo')
                            ->money('BRL')
                            ->placeholder('—'),

                        TextEntry::make('expected_salary_max')
                            ->label('Máximo')
                            ->money('BRL')
                            ->placeholder('—'),
                    ]),
            ]);
    }
}
