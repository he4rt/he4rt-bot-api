<?php

declare(strict_types=1);

namespace He4rt\Community\Retrospective\DTOs;

use Carbon\CarbonImmutable;

/**
 * Recorte temporal de uma retrospectiva. Toda fonte recebe o mesmo Period e
 * escopa suas queries pela coluna de tempo do evento (nunca created_at).
 */
final readonly class Period
{
    public function __construct(
        public CarbonImmutable $since,
        public CarbonImmutable $until,
    ) {}

    public static function of(CarbonImmutable $since, CarbonImmutable $until): self
    {
        return new self($since, $until);
    }

    /**
     * Identidade estável do recorte, para cachear varreduras caras por período
     * (ex.: os candidatos a exclusion que o Deck Builder pede a cada fonte).
     */
    public function cacheKey(): string
    {
        return $this->since->getTimestamp().'-'.$this->until->getTimestamp();
    }
}
