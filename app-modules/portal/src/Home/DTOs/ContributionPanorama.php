<?php

declare(strict_types=1);

namespace He4rt\Portal\Home\DTOs;

use Carbon\CarbonImmutable;

final readonly class ContributionPanorama
{
    /**
     * @param  list<ContributionSlice>  $composition
     * @param  list<ContributionMonth>  $timeline
     */
    public function __construct(
        public int $total,
        public int $people,
        public ?CarbonImmutable $since,
        public array $composition,
        public array $timeline,
    ) {}

    public function isEmpty(): bool
    {
        return $this->total === 0;
    }

    public function sinceLabel(): string
    {
        return $this->since?->translatedFormat('F \d\e Y') ?? '';
    }
}
