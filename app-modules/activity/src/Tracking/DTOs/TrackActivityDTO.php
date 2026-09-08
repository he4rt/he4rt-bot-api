<?php

declare(strict_types=1);

namespace He4rt\Activity\Tracking\DTOs;

use DateTimeImmutable;
use He4rt\Activity\Tracking\Enums\ActivityType;
use He4rt\Activity\Tracking\Enums\AttributionMethod;

final readonly class TrackActivityDTO
{
    public function __construct(
        public string $externalIdentityId,
        public ActivityType $type,
        public AttributionMethod $attributedBy,
        public DateTimeImmutable $occurredAt,
        public string $externalRef,
        public ?string $sourceType = null,
        public ?string $sourceId = null,
    ) {}
}
