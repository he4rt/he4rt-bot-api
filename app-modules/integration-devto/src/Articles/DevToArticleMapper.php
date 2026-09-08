<?php

declare(strict_types=1);

namespace He4rt\IntegrationDevTo\Articles;

use DateTimeImmutable;
use He4rt\Contents\Articles\DTOs\ArticleDTO;
use He4rt\Contents\Articles\DTOs\ArticleEngagementDTO;

final readonly class DevToArticleMapper
{
    /**
     * Mapeia um item de listagem (GET /articles?username=X ou /articles/me/published) para DTO raso.
     * Retorna null quando o nucleo obrigatorio falta (id, user.username, title, url, published_at).
     *
     * @param  array<string, mixed>  $payload
     */
    public function fromListing(array $payload): ?ArticleDTO
    {
        $externalId = $payload['id'] ?? null;
        $authorHandle = $payload['user']['username'] ?? null;
        $title = $payload['title'] ?? null;
        $url = $payload['url'] ?? null;
        $publishedAt = $payload['published_at'] ?? null;

        if (
            (!is_int($externalId) && !is_string($externalId))
            || !is_string($authorHandle) || $authorHandle === ''
            || !is_string($title) || $title === ''
            || !is_string($url) || $url === ''
            || !is_string($publishedAt) || $publishedAt === ''
        ) {
            return null;
        }

        $editedAt = $payload['edited_at'] ?? null;

        return new ArticleDTO(
            externalId: (string) $externalId,
            authorHandle: $authorHandle,
            title: $title,
            url: $url,
            publishedAt: new DateTimeImmutable($publishedAt),
            description: $this->stringOrNull($payload['description'] ?? null),
            thumbnailUrl: $this->stringOrNull($payload['cover_image'] ?? null),
            canonicalUrl: $this->stringOrNull($payload['canonical_url'] ?? null),
            readingTimeMinutes: is_int($payload['reading_time_minutes'] ?? null) ? $payload['reading_time_minutes'] : null,
            sourceEditedAt: is_string($editedAt) && $editedAt !== '' ? new DateTimeImmutable($editedAt) : null,
            engagement: new ArticleEngagementDTO(
                reactions: is_int($payload['public_reactions_count'] ?? null) ? $payload['public_reactions_count'] : null,
                comments: is_int($payload['comments_count'] ?? null) ? $payload['comments_count'] : null,
            ),
            tags: $this->normalizeTags($payload),
            detailHydrated: false,
        );
    }

    /**
     * Funde o payload de detalhe (GET /articles/{id}) sobre o DTO raso. SEMPRE detailHydrated=true.
     *
     * @param  array<string, mixed>  $payload
     */
    public function fromDetail(array $payload, ArticleDTO $shallow): ArticleDTO
    {
        $externalId = $payload['id'] ?? null;
        $authorHandle = $payload['user']['username'] ?? null;
        $editedAt = $payload['edited_at'] ?? null;

        $hasTagInfo = array_key_exists('tag_list', $payload) || array_key_exists('tags', $payload);

        return new ArticleDTO(
            externalId: (is_int($externalId) || is_string($externalId)) ? (string) $externalId : $shallow->externalId,
            authorHandle: is_string($authorHandle) && $authorHandle !== '' ? $authorHandle : $shallow->authorHandle,
            title: $this->stringOrNull($payload['title'] ?? null) ?? $shallow->title,
            url: $this->stringOrNull($payload['url'] ?? null) ?? $shallow->url,
            publishedAt: is_string($payload['published_at'] ?? null) && $payload['published_at'] !== ''
                ? new DateTimeImmutable($payload['published_at'])
                : $shallow->publishedAt,
            description: $this->stringOrNull($payload['description'] ?? null) ?? $shallow->description,
            thumbnailUrl: $this->stringOrNull($payload['cover_image'] ?? null) ?? $shallow->thumbnailUrl,
            canonicalUrl: $this->stringOrNull($payload['canonical_url'] ?? null) ?? $shallow->canonicalUrl,
            readingTimeMinutes: is_int($payload['reading_time_minutes'] ?? null)
                ? $payload['reading_time_minutes']
                : $shallow->readingTimeMinutes,
            bodyMarkdown: $this->stringOrNull($payload['body_markdown'] ?? null),
            bodyHtml: $this->stringOrNull($payload['body_html'] ?? null),
            sourceEditedAt: is_string($editedAt) && $editedAt !== '' ? new DateTimeImmutable($editedAt) : $shallow->sourceEditedAt,
            engagement: new ArticleEngagementDTO(
                reactions: is_int($payload['public_reactions_count'] ?? null)
                    ? $payload['public_reactions_count']
                    : $shallow->engagement?->reactions,
                comments: is_int($payload['comments_count'] ?? null)
                    ? $payload['comments_count']
                    : $shallow->engagement?->comments,
                saves: is_int($payload['reading_list_count'] ?? null) ? $payload['reading_list_count'] : null,
            ),
            tags: $hasTagInfo ? $this->normalizeTags($payload) : $shallow->tags,
            detailHydrated: true,
        );
    }

    /**
     * Normaliza tag_list/tags para list<string>. Quirk da API: na listagem 'tag_list' e array;
     * no detalhe 'tag_list' e string separada por virgula, com 'tags' array como chave irma.
     *
     * @param  array<string, mixed>  $payload
     * @return list<string>
     */
    private function normalizeTags(array $payload): array
    {
        $tagList = $payload['tag_list'] ?? null;

        if (is_array($tagList)) {
            return $this->cleanTags($tagList);
        }

        if (is_string($tagList) && $tagList !== '') {
            return $this->cleanTags(explode(',', $tagList));
        }

        $tags = $payload['tags'] ?? null;

        if (is_array($tags)) {
            return $this->cleanTags($tags);
        }

        return [];
    }

    /**
     * @param  array<array-key, mixed>  $tags
     * @return list<string>
     */
    private function cleanTags(array $tags): array
    {
        $cleaned = array_map(
            static fn (mixed $tag): string => is_string($tag) ? mb_trim($tag) : '',
            $tags,
        );

        return array_values(array_filter($cleaned, static fn (string $tag): bool => $tag !== ''));
    }

    private function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
