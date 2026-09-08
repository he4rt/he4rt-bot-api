<?php

declare(strict_types=1);

namespace He4rt\Community\Retrospective\Actions;

use He4rt\Community\Retrospective\Contracts\Slide;
use He4rt\Community\Retrospective\DTOs\DeckConfig;
use He4rt\Community\Retrospective\DTOs\RetrospectiveSnapshot;
use He4rt\Community\Retrospective\DTOs\SourceResult;

/**
 * Compositor único do deck: aplica a curadoria de apresentação (DeckConfig) sobre
 * um snapshot já coletado, sem tocar as fontes. É o render path compartilhado
 * entre a página pública (snapshot congelado) e o preview do admin (snapshot ao
 * vivo) — "ver rascunho" bate com o publicado (ADR-0002).
 *
 * Ordem e on/off (fonte/slide) re-derivam do snapshot aqui, baratos. Exclusions
 * NÃO são reaplicadas: elas mexem no dado e já entraram no collect que gerou o
 * snapshot (ADR-0001).
 */
final readonly class ComposeDeck
{
    /**
     * @return list<SourceResult>
     */
    public function execute(RetrospectiveSnapshot $snapshot, DeckConfig $config): array
    {
        $sources = [];

        foreach ($snapshot->sources as $source) {
            if (!$config->showsSource($source->key)) {
                continue;
            }

            $composed = $this->applySlideVisibility($source, $config);

            if (!$composed->isEmpty()) {
                $sources[] = $composed;
            }
        }

        usort(
            $sources,
            static fn (SourceResult $a, SourceResult $b): int => $config->position($a->key) <=> $config->position($b->key),
        );

        return $sources;
    }

    private function applySlideVisibility(SourceResult $source, DeckConfig $config): SourceResult
    {
        $slides = array_values(array_filter(
            $source->slides,
            static fn (Slide $slide): bool => $config->showsSlide($slide->kind()),
        ));

        return new SourceResult($source->key, $source->label, $source->headline, $slides);
    }
}
