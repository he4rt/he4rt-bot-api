<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Contributions\Timeline\DTOs;

use Carbon\CarbonImmutable;

/**
 * `dataUntil` é o que separa zero de lacuna: dia posterior a ele não é um dia
 * sem atividade, é um dia sem ingestão — e a linha do tempo o hachura.
 */
final readonly class TimelineMeta
{
    public function __construct(
        public CarbonImmutable $since,
        public CarbonImmutable $until,
        public CarbonImmutable $dataUntil,
        public string $timezone,
    ) {}

    public function days(): int
    {
        return (int) $this->since->diffInDays($this->until) + 1;
    }

    /**
     * @return array{since: string, until: string, dataUntil: string, days: int, timezone: string}
     */
    public function toArray(): array
    {
        return [
            'since' => $this->since->toDateString(),
            'until' => $this->until->toDateString(),
            'dataUntil' => $this->dataUntil->toDateString(),
            'days' => $this->days(),
            'timezone' => $this->timezone,
        ];
    }
}
