<?php

declare(strict_types=1);

namespace He4rt\IntegrationDevTo;

use He4rt\Contents\Articles\ArticleProviderRegistry;
use He4rt\IntegrationDevTo\Articles\DevToArticleProvider;
use Illuminate\Support\ServiceProvider;

class IntegrationDevToServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/integration-devto.php', 'integration-devto');
    }

    public function boot(): void
    {
        $this->app->make(ArticleProviderRegistry::class)
            ->register($this->app->make(DevToArticleProvider::class));
    }
}
