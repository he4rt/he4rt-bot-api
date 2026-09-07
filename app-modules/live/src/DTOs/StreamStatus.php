<?php

declare(strict_types=1);

namespace He4rt\Live\DTOs;

use Carbon\CarbonImmutable;

/** Estado atual do stream segundo a Control API do mediamtx. */
final readonly class StreamStatus
{
    public function __construct(
        public bool $onAir,
        public ?CarbonImmutable $startedAt,
    ) {}

    public static function offline(): self
    {
        return new self(onAir: false, startedAt: null);
    }
}
