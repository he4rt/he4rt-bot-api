<?php

declare(strict_types=1);

namespace He4rt\Contents\Articles\Actions;

use He4rt\Contents\Articles\DTOs\ArticleDTO;
use He4rt\Contents\Articles\Events\ArticlePublished;
use He4rt\Contents\Articles\Models\Article;
use He4rt\Contents\Data\TagList;
use He4rt\Contents\Enums\ContentProvider;
use He4rt\Contents\Models\ContentEntry;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class UpsertArticle
{
    public function execute(ContentProvider $provider, ArticleDTO $dto): ContentEntry
    {
        $authorId = $this->resolveAuthorId($provider, $dto);

        return DB::transaction(function () use ($provider, $dto, $authorId): ContentEntry {
            /** @var ContentEntry|null $entry */
            $entry = ContentEntry::query()
                ->with('contentable')
                ->where('provider', $provider)
                ->where('external_id', $dto->externalId)
                ->first();

            $hadAuthorBefore = $entry?->author_id !== null;

            if ($entry === null) {
                $article = Article::query()->create($this->articleAttributes($dto));

                $entry = ContentEntry::query()->create([
                    'contentable_type' => 'content_article',
                    'contentable_id' => $article->id,
                    'author_id' => $authorId,
                    'provider' => $provider,
                    'external_id' => $dto->externalId,
                    ...$this->entryAttributes($dto),
                ]);

                $entry->setRelation('contentable', $article);
            } else {
                if ($entry->contentable instanceof Article) {
                    $entry->contentable->update($this->articleAttributes($dto));
                }

                $entry->update([
                    'author_id' => $authorId ?? $entry->author_id,
                    ...$this->entryAttributes($dto),
                ]);
            }

            $hasAuthorNow = $entry->author_id !== null;

            if (!$hadAuthorBefore && $hasAuthorNow) {
                event(new ArticlePublished($entry));
            }

            return $entry;
        });
    }

    /** @return array<string, mixed> */
    private function entryAttributes(ArticleDTO $dto): array
    {
        $attributes = [
            'author_handle' => $dto->authorHandle,
            'title' => $dto->title,
            'url' => $dto->url,
            'thumbnail_url' => $dto->thumbnailUrl,
            'tags' => TagList::fromArray($dto->tags),
            'published_at' => $dto->publishedAt,
            'reactions_count' => $dto->engagement?->reactions,
            'comments_count' => $dto->engagement?->comments,
            'metrics_synced_at' => now(),
        ];

        if ($dto->detailHydrated) {
            $attributes['saves_count'] = $dto->engagement?->saves;
        }

        return $attributes;
    }

    /** @return array<string, mixed> */
    private function articleAttributes(ArticleDTO $dto): array
    {
        $attributes = [
            'description' => $dto->description,
            'canonical_url' => $dto->canonicalUrl,
            'reading_time_minutes' => $dto->readingTimeMinutes,
        ];

        if ($dto->detailHydrated) {
            $attributes['body_markdown'] = $dto->bodyMarkdown;
            $attributes['body_html'] = $dto->bodyHtml;
            $attributes['source_edited_at'] = $dto->sourceEditedAt;
        }

        return $attributes;
    }

    private function resolveAuthorId(ContentProvider $provider, ArticleDTO $dto): ?string
    {
        $identityProvider = $provider->toIdentityProvider();

        if (!$identityProvider instanceof IdentityProvider) {
            return null;
        }

        $identity = ExternalIdentity::query()
            ->whereMorphedTo('model', User::class)
            ->where('provider', $identityProvider)
            ->where('metadata->username', $dto->authorHandle)
            ->whereNotNull('connected_at')
            ->whereNull('disconnected_at')
            ->first();

        return $identity?->model_id;
    }
}
