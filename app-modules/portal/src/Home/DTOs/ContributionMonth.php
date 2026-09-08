<?php

declare(strict_types=1);

namespace He4rt\Portal\Home\DTOs;

use Carbon\CarbonImmutable;

final readonly class ContributionMonth
{
    public function __construct(
        public string $label,
        public string $fullLabel,
        public int $count,
        public float $height,
    ) {}

    public static function make(CarbonImmutable $month, int $count, int $peak): self
    {
        return new self(
            label: $month->translatedFormat('M'),
            fullLabel: $month->translatedFormat('F \d\e Y'),
            count: $count,
            // Mês sem contribuição some se a altura for zero; um traço mínimo
            // mostra que o mês existe e ficou vazio.
            height: $peak > 0 ? max(2.0, round(($count / $peak) * 100, 2)) : 2.0,
        );
    }
}
