<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Live\Resources;

use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use He4rt\Live\Models\Live;
use He4rt\PanelAdmin\Live\Resources\LiveResource\Pages\ListLives;
use He4rt\PanelAdmin\Live\Resources\LiveResource\Pages\ViewLive;

class LiveResource extends Resource
{
    protected static ?string $model = Live::class;

    protected static ?string $slug = 'lives';

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedVideoCamera;

    public static function getNavigationLabel(): string
    {
        return 'Lives';
    }

    public static function getModelLabel(): string
    {
        return 'Live';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Lives';
    }

    /**
     * A criação da live passa pela action `createLive` da listagem, que
     * garante a live corrente única. Uma tela de criação padrão contornaria
     * essa garantia.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->label('Título')
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),

                TextColumn::make('started_at')
                    ->label('Início')
                    ->dateTime('d/m/Y H:i')
                    ->timezone(config('app.display_timezone'))
                    ->placeholder('—'),

                TextColumn::make('ended_at')
                    ->label('Encerramento')
                    ->dateTime('d/m/Y H:i')
                    ->timezone(config('app.display_timezone'))
                    ->placeholder('—'),

                TextColumn::make('peak_viewers')
                    ->label('Pico de espectadores')
                    ->numeric()
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListLives::route('/'),
            'view' => ViewLive::route('/{record}'),
        ];
    }
}
