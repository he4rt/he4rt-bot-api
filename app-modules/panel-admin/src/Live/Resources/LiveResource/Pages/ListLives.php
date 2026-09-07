<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Live\Resources\LiveResource\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use He4rt\Live\Actions\CreateLive;
use He4rt\Live\Exceptions\CurrentLiveAlreadyExists;
use He4rt\PanelAdmin\Live\Resources\LiveResource;
use Illuminate\Database\QueryException;

class ListLives extends ListRecords
{
    protected static string $resource = LiveResource::class;

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('createLive')
                ->label('Criar live')
                ->icon(Heroicon::OutlinedPlus)
                ->schema([
                    TextInput::make('title')
                        ->label('Título')
                        ->required()
                        ->maxLength(255),

                    Textarea::make('description')
                        ->label('Descrição'),
                ])
                ->action(function (array $data): void {
                    $title = is_string($data['title'] ?? null) ? $data['title'] : '';
                    $description = is_string($data['description'] ?? null) ? $data['description'] : null;

                    try {
                        resolve(CreateLive::class)->execute($title, $description);

                        Notification::make()
                            ->success()
                            ->title('Live criada')
                            ->send();
                    } catch (CurrentLiveAlreadyExists|QueryException) {
                        Notification::make()
                            ->danger()
                            ->title('Não foi possível criar a live')
                            ->body(CurrentLiveAlreadyExists::make()->getMessage())
                            ->send();
                    }
                }),
        ];
    }
}
