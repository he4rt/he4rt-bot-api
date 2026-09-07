<?php

declare(strict_types=1);

namespace He4rt\Live;

use He4rt\Live\Audience\RedisViewerPresence;
use He4rt\Live\Console\SampleLiveViewersCommand;
use He4rt\Live\Console\SimulateLiveChatCommand;
use He4rt\Live\Contracts\ViewerPresenceContract;
use Illuminate\Support\ServiceProvider;

class LiveServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/live.php', 'live');

        $this->app->singleton(ViewerPresenceContract::class, RedisViewerPresence::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                SampleLiveViewersCommand::class,
                SimulateLiveChatCommand::class,
            ]);
        }
    }
}
