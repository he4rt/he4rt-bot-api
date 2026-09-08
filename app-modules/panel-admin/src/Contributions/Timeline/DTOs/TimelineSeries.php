<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Contributions\Timeline\DTOs;

final readonly class TimelineSeries
{
    /**
     * @param  list<TimelineDay>  $days
     * @param  list<TimelineType>  $types
     */
    public function __construct(
        public TimelineMeta $meta,
        public array $days,
        public array $types,
    ) {}

    /**
     * @return array{meta: array<string, mixed>, days: list<array<string, mixed>>, types: list<array<string, mixed>>}
     */
    public function toArray(): array
    {
        return [
            'meta' => $this->meta->toArray(),
            'days' => array_map(static fn (TimelineDay $day): array => $day->toArray(), $this->days),
            'types' => array_map(static fn (TimelineType $type): array => $type->toArray(), $this->types),
        ];
    }
}
