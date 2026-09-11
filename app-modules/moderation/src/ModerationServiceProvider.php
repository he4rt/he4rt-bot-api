<?php

declare(strict_types=1);

namespace He4rt\Moderation;

use He4rt\Moderation\Audit\RecordAuditLog;
use He4rt\Moderation\Cases\Events\CaseCreated;
use He4rt\Moderation\Cases\Events\CaseResolved;
use He4rt\Moderation\Enforcement\ActionExecuted;
use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Platform\PlatformRegistry;
use He4rt\Moderation\Platform\WebModerationAdapter;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class ModerationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/moderation.php', 'moderation');

        $this->app->singleton(static function (): PlatformRegistry {
            $registry = new PlatformRegistry();
            $registry->register(Platform::Web, WebModerationAdapter::class);

            return $registry;
        });

        $this->app->singleton(WebModerationAdapter::class);
        $this->app->tag([WebModerationAdapter::class], 'moderation.platforms');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'moderation');

        Event::listen(CaseCreated::class, [RecordAuditLog::class, 'handleCaseCreated']);
        Event::listen(CaseResolved::class, [RecordAuditLog::class, 'handleCaseResolved']);
        Event::listen(ActionExecuted::class, [RecordAuditLog::class, 'handleActionExecuted']);
    }
}
