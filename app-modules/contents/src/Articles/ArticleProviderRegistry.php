<?php

declare(strict_types=1);

namespace He4rt\Contents\Articles;

use He4rt\Contents\Articles\Contracts\ArticleProvider;

final class ArticleProviderRegistry
{
    /** @var list<ArticleProvider> */
    private array $providers = [];

    public function register(ArticleProvider $provider): void
    {
        $this->providers[] = $provider;
    }

    /** @return list<ArticleProvider> */
    public function all(): array
    {
        return $this->providers;
    }
}
