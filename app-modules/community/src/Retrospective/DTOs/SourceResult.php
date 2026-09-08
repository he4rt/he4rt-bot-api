<?php

declare(strict_types=1);

namespace He4rt\Community\Retrospective\DTOs;

use He4rt\Community\Retrospective\Contracts\Slide;

/**
 * O que uma fonte devolve para um Period: um envelope de chips para o cover
 * compartilhado (HeadlineMetrics) e um bloco ordenado de Slides. Carrega a
 * identidade da fonte (key/label) para o cover agrupar os chips por fonte, sem
 * soma cruzada (ADR-0001).
 */
final readonly class SourceResult
{
    /**
     * @param  list<Slide>  $slides
     */
    public function __construct(
        public string $key,
        public string $label,
        public HeadlineMetrics $headline,
        public array $slides = [],
    ) {}

    public function isEmpty(): bool
    {
        return $this->slides === [] && $this->headline->isEmpty();
    }
}
