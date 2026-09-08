<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\ContentEntries\Pages;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use He4rt\Contents\Articles\Models\Article;
use He4rt\Contents\Models\ContentEntry;
use He4rt\PanelAdmin\Filament\Resources\ContentEntries\ContentEntryResource;

class ViewContentEntry extends ViewRecord
{
    protected static string $resource = ContentEntryResource::class;

    /**
     * O título do artigo, sem o prefixo "View" do Filament — ele aparecia
     * junto do breadcrumb e de novo no corpo, três vezes na mesma dobra.
     */
    public function getTitle(): string
    {
        return $this->entry()->title;
    }

    /**
     * O breadcrumb padrão traz o título do registro logo acima do cabeçalho
     * que já o mostra. Aqui fica só a trilha de navegação.
     *
     * @return array<int|string, string>
     */
    public function getBreadcrumbs(): array
    {
        return [
            ContentEntryResource::getUrl() => 'Artigos',
            'Detalhes',
        ];
    }

    public function getSubheading(): string
    {
        $entry = $this->entry();

        $parts = [
            $entry->provider->getLabel(),
            $entry->published_at
                ->timezone(config('app.display_timezone'))
                ->format('d/m/Y'),
        ];

        $article = $entry->contentable;

        if ($article instanceof Article && $article->reading_time_minutes !== null) {
            $parts[] = sprintf('%d min de leitura', $article->reading_time_minutes);
        }

        return implode(' · ', $parts);
    }

    /**
     * @return array<int, Action|EditAction>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('openSource')
                ->label('Abrir no provider')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url(fn (): string => $this->entry()->url)
                ->openUrlInNewTab(),

            EditAction::make(),
        ];
    }

    private function entry(): ContentEntry
    {
        /** @var ContentEntry $record */
        $record = $this->getRecord();

        return $record;
    }
}
