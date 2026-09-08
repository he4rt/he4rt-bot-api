<?php

declare(strict_types=1);

namespace He4rt\Community\Retrospective\Contracts;

use He4rt\Community\Retrospective\DTOs\Metric;
use He4rt\Community\Retrospective\DTOs\Period;
use He4rt\Community\Retrospective\DTOs\PersonIdentity;
use He4rt\Community\Retrospective\DTOs\SourceFilters;

/**
 * Interface segregada de medição individual (ISP, ADR-0001): a fonte que sabe
 * medir UMA pessoa no recorte a implementa, e `RetrospectiveSource` segue com
 * `collect()` como único método obrigatório.
 *
 * É o que permite ao slide da tag He4rt mostrar "8.1k mensagens · 47 PRs" sem
 * nenhuma fonte olhar a irmã: cada uma devolve as SUAS métricas, o community
 * empilha por fonte e ninguém soma nada entre plataformas — o total cruzado
 * nasce na orquestração, exatamente onde o ADR-0001 o permite.
 *
 * A implementação varre dado por pessoa, então cabe a ela escopar pelo Period e
 * cachear (a mesma obrigação de `exclusionCandidates()`).
 */
interface MeasuresPerson
{
    /**
     * @return list<Metric> vazio quando a pessoa não tem conta nesta fonte
     */
    public function measure(PersonIdentity $person, Period $period, SourceFilters $filters): array;
}
