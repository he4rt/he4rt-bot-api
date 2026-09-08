<?php

declare(strict_types=1);

namespace He4rt\Community\Retrospective\DTOs;

/**
 * Envelope de chips de uma fonte para o cover compartilhado. Ordenado; o portal
 * renderiza os chips na ordem em que a fonte os emitiu.
 */
final readonly class HeadlineMetrics
{
    /**
     * @param  list<Metric>  $metrics
     */
    public function __construct(
        public array $metrics = [],
    ) {}

    public function isEmpty(): bool
    {
        return $this->metrics === [];
    }
}
