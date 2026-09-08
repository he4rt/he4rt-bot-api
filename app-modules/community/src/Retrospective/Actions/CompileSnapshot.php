<?php

declare(strict_types=1);

namespace He4rt\Community\Retrospective\Actions;

use He4rt\Community\Retrospective\Contracts\RetrospectiveSource;
use He4rt\Community\Retrospective\DTOs\Period;
use He4rt\Community\Retrospective\DTOs\PromotionEntry;
use He4rt\Community\Retrospective\DTOs\RetrospectiveSnapshot;
use He4rt\Community\Retrospective\DTOs\SourceFilters;

/**
 * Coleta todas as fontes registradas para um Period + filtros e empacota o
 * resultado cru num RetrospectiveSnapshot. É o que o publish congela.
 *
 * As promoções entram aqui pelo mesmo motivo dos números das fontes: são dado
 * medido no recorte, e o publish precisa congelá-las para a página pública não
 * consultar o banco por pessoa a cada visita.
 *
 * Não ordena nem cura: a ordem e o on/off vivem no DeckConfig e são aplicados
 * depois pelo ComposeDeck. Só descarta fontes vazias (não há o que congelar).
 * Adicionar uma fonte não toca esta classe: basta a tag "retrospective.source".
 */
final readonly class CompileSnapshot
{
    /** @var list<RetrospectiveSource> */
    private array $sources;

    /**
     * @param  iterable<RetrospectiveSource>  $sources
     */
    public function __construct(
        iterable $sources,
        private ComposePromotions $promotions,
    ) {
        $this->sources = array_values(
            is_array($sources) ? $sources : iterator_to_array($sources, preserve_keys: false),
        );
    }

    /**
     * @param  list<PromotionEntry>  $promotions  as pessoas escolhidas para o slide
     *                                            da tag He4rt; medidas aqui para congelarem junto dos números das fontes
     */
    public function execute(Period $period, SourceFilters $filters, array $promotions = []): RetrospectiveSnapshot
    {
        $results = [];

        foreach ($this->sources as $source) {
            $result = $source->collect($period, $filters);

            if (!$result->isEmpty()) {
                $results[] = $result;
            }
        }

        // Os filtros vão junto: o snapshot precisa saber o que o produziu para o
        // painel detectar exclusion alterada depois de publicar.
        return new RetrospectiveSnapshot(
            $results,
            $filters,
            $this->promotions->execute($promotions, $period, $filters),
        );
    }
}
