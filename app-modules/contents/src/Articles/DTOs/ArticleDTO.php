<?php

declare(strict_types=1);

namespace He4rt\Contents\Articles\DTOs;

use DateTimeImmutable;

final readonly class ArticleDTO
{
    /** @param list<string> $tags */
    public function __construct(
        public string $externalId,
        public string $authorHandle,
        public string $title,
        public string $url,
        public DateTimeImmutable $publishedAt,
        public ?string $description = null,
        public ?string $thumbnailUrl = null,
        public ?string $canonicalUrl = null,
        public ?int $readingTimeMinutes = null,
        public ?string $bodyMarkdown = null,
        public ?string $bodyHtml = null,
        public ?DateTimeImmutable $sourceEditedAt = null,
        public ?ArticleEngagementDTO $engagement = null,
        public array $tags = [],
        public bool $detailHydrated = false,
    ) {}
}
