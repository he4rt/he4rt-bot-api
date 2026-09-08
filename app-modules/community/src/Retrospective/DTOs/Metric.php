<?php

declare(strict_types=1);

namespace He4rt\Community\Retrospective\DTOs;

/**
 * Um chip do cover: um número rotulado de uma fonte. Sem soma cruzada entre
 * fontes (decisão ADR-0001): cada fonte é a sua própria verdade, o cover só
 * concatena os chips por fonte. Formatação de exibição (ex.: milhares) fica no
 * portal.
 */
final readonly class Metric
{
    public function __construct(
        public string $label,
        public int $value,
    ) {}
}
