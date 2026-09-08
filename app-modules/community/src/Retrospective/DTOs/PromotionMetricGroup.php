<?php

declare(strict_types=1);

namespace He4rt\Community\Retrospective\DTOs;

/**
 * As métricas de UMA fonte para uma pessoa, com a fonte à mostra ("Discord:
 * 8.132 mensagens · 120h"). O agrupamento é o que impede a soma cruzada de
 * voltar pela porta dos fundos: um PR e uma mensagem nunca caem no mesmo total
 * porque nunca caem na mesma faixa (ADR-0001).
 */
final readonly class PromotionMetricGroup
{
    /**
     * @param  list<Metric>  $metrics
     */
    public function __construct(
        public string $sourceKey,
        public string $sourceLabel,
        public array $metrics = [],
    ) {}

    public function isEmpty(): bool
    {
        return $this->metrics === [];
    }
}
