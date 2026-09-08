<?php

declare(strict_types=1);

namespace He4rt\Portal\Home\DTOs;

use He4rt\Activity\Tracking\Enums\ActivityType;

final readonly class ContributionSlice
{
    public function __construct(
        public string $label,
        public int $count,
        public float $share,
        public string $color,
    ) {}

    public static function fromType(ActivityType $type, int $count, int $total): self
    {
        return new self(
            label: $type->getLabel(),
            count: $count,
            share: $total > 0 ? round(($count / $total) * 100, 2) : 0.0,
            // A cor do tipo já tem dono: o enum. O portal só escolhe o tom que
            // funciona sobre o fundo escuro da home.
            color: $type->getColor()[400],
        );
    }
}
