<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Retrospectives\Pages;

use Filament\Resources\Pages\CreateRecord;
use He4rt\Community\Retrospective\DTOs\DeckConfig;
use He4rt\Community\Retrospective\Enums\RetrospectiveStatus;
use He4rt\PanelAdmin\Filament\Resources\Retrospectives\RetrospectiveResource;
use He4rt\PanelAdmin\Filament\Resources\Retrospectives\Support\AvailableSources;

class CreateRetrospective extends CreateRecord
{
    protected static string $resource = RetrospectiveResource::class;

    /**
     * Semeia a ordem editorial com todas as fontes registradas. O builder também
     * aceitaria `order` vazia (fonte fora da lista vai para o fim), mas partir da
     * lista completa dá ao operador uma timeline já ancorada para reordenar.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = RetrospectiveStatus::Draft;
        $data['deck_config'] = new DeckConfig(order: AvailableSources::keys());

        return $data;
    }
}
