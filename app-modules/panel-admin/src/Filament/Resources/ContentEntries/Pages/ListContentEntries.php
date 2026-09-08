<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\ContentEntries\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use He4rt\Contents\Enums\ContentProvider;
use He4rt\PanelAdmin\Filament\Resources\ContentEntries\ContentEntryResource;
use He4rt\PanelAdmin\Filament\Resources\ContentEntries\Widgets\ContentEntryStatsWidget;
use Illuminate\Support\Facades\Artisan;

class ListContentEntries extends ListRecords
{
    protected static string $resource = ContentEntryResource::class;

    /**
     * @return array<int, class-string>
     */
    protected function getHeaderWidgets(): array
    {
        return [
            ContentEntryStatsWidget::class,
        ];
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('sync')
                ->label('Sincronizar artigos')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('primary')
                ->modalHeading('Sincronizar artigos')
                ->modalDescription('A sincronização vai para a fila e conversa com a API do provider. O acervo é atualizado assim que ela termina.')
                ->modalSubmitActionLabel('Enfileirar')
                ->action(function (): void {
                    // Enfileirado, não síncrono: o comando chama a API do
                    // provider e paginaria além do timeout da requisição.
                    Artisan::queue('contents:sync-articles', [
                        '--provider' => array_map(
                            static fn (ContentProvider $provider): string => $provider->value,
                            ContentProvider::cases(),
                        ),
                    ]);

                    Notification::make()
                        ->title('Sincronização enfileirada')
                        ->body('O acervo é atualizado quando a fila processar o comando.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
