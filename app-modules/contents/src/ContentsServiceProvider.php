<?php

declare(strict_types=1);

namespace He4rt\Contents;

use He4rt\Contents\Articles\Actions\ReconcileOrphanEntries;
use He4rt\Contents\Articles\ArticleProviderRegistry;
use He4rt\Contents\Articles\Console\SyncArticlesCommand;
use He4rt\Contents\Articles\Models\Article;
use He4rt\Contents\Models\ContentEntry;
use He4rt\Identity\ExternalIdentity\Events\ExternalIdentityConnected;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class ContentsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ArticleProviderRegistry::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        Relation::morphMap([
            'content_entry' => ContentEntry::class,
            'content_article' => Article::class,
        ]);

        Event::listen(ExternalIdentityConnected::class, [ReconcileOrphanEntries::class, 'handle']);

        if ($this->app->runningInConsole()) {
            $this->commands([SyncArticlesCommand::class]);

            $this->app->booted(function (): void {
                $schedule = $this->app->make(Schedule::class);
                $schedule->command('contents:sync-articles')->everyThirtyMinutes();
            });
        }
    }
}
