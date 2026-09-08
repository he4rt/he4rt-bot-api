<?php

declare(strict_types=1);

namespace He4rt\Contents\Articles\DTOs;

final readonly class ArticleEngagementDTO
{
    public function __construct(
        public ?int $reactions = null,
        public ?int $comments = null,
        public ?int $saves = null,
    ) {}
}
