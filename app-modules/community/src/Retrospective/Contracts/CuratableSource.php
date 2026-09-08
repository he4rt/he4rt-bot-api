<?php

declare(strict_types=1);

namespace He4rt\Community\Retrospective\Contracts;

use He4rt\Community\Retrospective\DTOs\ExclusionCandidate;
use He4rt\Community\Retrospective\DTOs\Period;
use He4rt\Community\Retrospective\DTOs\SlideDescriptor;

/**
 * Interface segregada de curadoria (ISP, ADR-0001): só a fonte que sabe se
 * descrever a implementa, e RetrospectiveSource segue com collect() como único
 * método obrigatório. O Deck Builder checa instanceof — uma fonte que não cura
 * continua entrando no deck, só sem catálogo de slides nem picker de exclusions.
 */
interface CuratableSource
{
    /**
     * Slides que a fonte PODE emitir, resolvido sem tocar o dado: é o catálogo
     * que o builder liga/desliga (DeckConfig::hiddenSlides). Um kind pode render
     * vários slides (github.repos = um card por repo), então o on/off é por kind,
     * não por instância.
     *
     * @return list<SlideDescriptor>
     */
    public function slideCatalog(): array;

    /**
     * Itens e pessoas que o operador pode esconder do deck naquele recorte.
     * Diferente de slideCatalog(), varre dado: a implementação mantém a consulta
     * escopada pelo Period e com LIMIT (as tabelas são grandes em produção).
     *
     * @return list<ExclusionCandidate>
     */
    public function exclusionCandidates(Period $period): array;
}
