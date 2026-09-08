<?php

declare(strict_types=1);

namespace He4rt\Portal\Articles\DTOs;

use Carbon\CarbonImmutable;
use He4rt\Contents\Articles\Models\Article as CatalogueArticle;
use He4rt\Contents\Models\ContentEntry;
use He4rt\Identity\User\Models\User;

final readonly class Article
{
    /**
     * @param  list<string>  $tags
     */
    public function __construct(
        public string $title,
        public string $description,
        public string $url,
        public CarbonImmutable $publishedAt,
        public int $reactions,
        public int $comments,
        public int $readingMinutes,
        public ?string $coverImage,
        public array $tags,
        public string $authorName,
        public string $authorUsername,
        public string $authorAvatar,
    ) {}

    /**
     * O detalhe do artigo (descrição, tempo de leitura) mora no contentable, que
     * o catálogo carrega por morph e pode não estar hidratado ainda.
     *
     * Métricas nulas significam que o provider não as mede — para a vitrine, zero.
     */
    public static function fromEntry(ContentEntry $entry): self
    {
        $detail = $entry->contentable instanceof CatalogueArticle ? $entry->contentable : null;
        $author = $entry->author instanceof User ? $entry->author : null;

        return new self(
            title: $entry->title,
            description: $detail->description ?? '',
            url: $entry->url,
            publishedAt: $entry->published_at->toImmutable(),
            reactions: $entry->reactions_count ?? 0,
            comments: $entry->comments_count ?? 0,
            readingMinutes: $detail->reading_time_minutes ?? 0,
            coverImage: $entry->thumbnail_url,
            tags: $entry->tags->toArray(),
            // Sem identidade vinculada só temos o handle da fonte: é ele que assina.
            authorName: $author->name ?? $entry->author_handle,
            authorUsername: $entry->author_handle,
            authorAvatar: $author?->getFilamentAvatarUrl() ?? '',
        );
    }

    public function publishedLabel(): string
    {
        return $this->publishedAt
            ->timezone(config()->string('app.display_timezone'))
            ->translatedFormat('M \d\e Y');
    }
}
