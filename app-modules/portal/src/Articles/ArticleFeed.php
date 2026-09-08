<?php

declare(strict_types=1);

namespace He4rt\Portal\Articles;

use Carbon\CarbonImmutable;
use He4rt\Contents\Models\ContentEntry;
use He4rt\Portal\Articles\DTOs\Article;
use He4rt\Portal\Articles\DTOs\ArticleAuthor;
use He4rt\Portal\Articles\DTOs\ArticleTopic;
use Illuminate\Support\Collection;

/**
 * Read model do acervo de artigos da comunidade.
 *
 * A fonte é o catálogo do módulo `contents`, alimentado por `contents:sync-articles`.
 * Ele guarda também quem publicou sem identidade vinculada, então a página credita
 * todo mundo que escreveu pela organização.
 */
final class ArticleFeed
{
    /** @var list<Article>|null */
    private ?array $articles = null;

    /** @return list<Article> */
    public function articles(): array
    {
        return $this->articles ??= array_values(
            ContentEntry::query()
                ->with(['contentable', 'author'])
                ->latest('published_at')
                ->get()
                ->map(fn (ContentEntry $entry): Article => Article::fromEntry($entry))
                ->all(),
        );
    }

    /**
     * Query própria em vez de fatiar articles(): a vitrine da home precisa de três
     * cards e não do acervo inteiro em memória.
     *
     * @return list<Article>
     */
    public function latest(int $limit): array
    {
        return array_values(
            ContentEntry::query()
                ->with(['contentable', 'author'])
                ->latest('published_at')
                ->limit($limit)
                ->get()
                ->map(fn (ContentEntry $entry): Article => Article::fromEntry($entry))
                ->all(),
        );
    }

    /**
     * Ordenados por volume e, no empate, por alcance. As duas métricas divergem
     * de propósito — quem escreveu uma vez pode ter mais reações que quem
     * escreveu quatro —, e a coluna de pessoas mostra as duas.
     *
     * @return list<ArticleAuthor>
     */
    public function authors(): array
    {
        return Collection::make($this->articles())
            ->groupBy(fn (Article $article): string => $article->authorUsername)
            ->map(function (Collection $items, string $username): ArticleAuthor {
                /** @var Article $first */
                $first = $items->first();

                return new ArticleAuthor(
                    username: $username,
                    name: $first->authorName !== '' ? $first->authorName : $username,
                    avatar: $first->authorAvatar,
                    articleCount: $items->count(),
                    reactions: (int) $items->sum(fn (Article $article): int => $article->reactions),
                );
            })
            ->sortByDesc(fn (ArticleAuthor $author): int => $author->articleCount * 100_000 + $author->reactions)
            ->pipe(fn (Collection $authors): array => array_values($authors->all()));
    }

    /** @return list<ArticleTopic> */
    public function topics(): array
    {
        return Collection::make($this->articles())
            ->flatMap(fn (Article $article): array => $article->tags)
            ->countBy()
            ->map(fn (int $count, string $tag): ArticleTopic => new ArticleTopic($tag, $count))
            ->sortByDesc(fn (ArticleTopic $topic): int => $topic->count)
            ->pipe(fn (Collection $topics): array => array_values($topics->all()));
    }

    /**
     * @return array{articles: int, authors: int, topics: int, reactions: int}
     */
    public function stats(): array
    {
        return [
            'articles' => count($this->articles()),
            'authors' => count($this->authors()),
            'topics' => count($this->topics()),
            'reactions' => (int) Collection::make($this->articles())->sum(fn (Article $article): int => $article->reactions),
        ];
    }

    /**
     * O mais reagido dos últimos 12 meses, não o campeão histórico: o campeão é
     * um guia de 2023 já usado como CTA em outro lugar do site, e repeti-lo aqui
     * gastaria a primeira dobra com algo conhecido.
     */
    public function highlight(): ?Article
    {
        $cutoff = CarbonImmutable::now()->subYear();

        $recent = Collection::make($this->articles())
            ->filter(fn (Article $article): bool => $article->publishedAt->greaterThanOrEqualTo($cutoff));

        $pool = $recent->isNotEmpty() ? $recent : Collection::make($this->articles());

        return $pool->sortByDesc(fn (Article $article): int => $article->reactions)->first();
    }
}
