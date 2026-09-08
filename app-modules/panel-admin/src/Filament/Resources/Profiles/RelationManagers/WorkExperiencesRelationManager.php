<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Profiles\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use He4rt\Profile\Models\WorkExperience;

class WorkExperiencesRelationManager extends RelationManager
{
    protected static string $relationship = 'workExperiences';

    protected static ?string $title = 'Experiências';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('company_name')
                    ->label('Empresa')
                    ->required()
                    ->maxLength(255),

                TextInput::make('position')
                    ->label('Cargo')
                    ->required()
                    ->maxLength(255),

                Textarea::make('description')
                    ->label('Descrição')
                    ->rows(4)
                    ->required()
                    ->maxLength(5_000)
                    ->columnSpanFull(),

                DatePicker::make('start_date')
                    ->label('Início')
                    ->required()
                    ->maxDate(now())
                    ->native(condition: false)
                    ->displayFormat('d/m/Y'),

                Toggle::make('is_currently_working_here')
                    ->label('Trabalha aqui atualmente')
                    ->live()
                    ->afterStateUpdated(static function (Set $set, bool $state): void {
                        if ($state) {
                            $set('end_date', null);
                        }
                    }),

                DatePicker::make('end_date')
                    ->label('Término')
                    ->native(condition: false)
                    ->displayFormat('d/m/Y')
                    ->afterOrEqual('start_date')
                    ->disabled(static fn (Get $get): bool => (bool) $get('is_currently_working_here'))
                    // Campo desabilitado não é dehidratado por padrão; sem isto o
                    // término antigo sobreviveria ao marcar "trabalha aqui".
                    ->dehydrated()
                    ->required(static fn (Get $get): bool => !$get('is_currently_working_here')),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('company_name')
            ->defaultSort('start_date', 'desc')
            ->columns([
                TextColumn::make('company_name')
                    ->label('Empresa')
                    ->searchable()
                    ->sortable()
                    ->description(static fn (WorkExperience $record): string => $record->position),

                TextColumn::make('start_date')
                    ->label('Início')
                    ->date('m/Y')
                    ->sortable(),

                TextColumn::make('end_date')
                    ->label('Término')
                    ->date('m/Y')
                    ->placeholder('Atual'),

                IconColumn::make('is_currently_working_here')
                    ->label('Atual')
                    ->boolean(),
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
