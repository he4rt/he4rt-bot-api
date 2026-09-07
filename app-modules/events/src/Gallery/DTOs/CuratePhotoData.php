<?php

declare(strict_types=1);

namespace He4rt\Events\Gallery\DTOs;

final readonly class CuratePhotoData
{
    public function __construct(
        public ?string $caption,
        public bool $highlight,
    ) {}
}
