<?php

declare(strict_types=1);

namespace He4rt\Portal\Articles\DTOs;

final readonly class ArticleAuthor
{
    public function __construct(
        public string $username,
        public string $name,
        public string $avatar,
        public int $articleCount,
        public int $reactions,
    ) {}
}
