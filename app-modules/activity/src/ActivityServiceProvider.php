<?php

declare(strict_types=1);

namespace He4rt\Activity;

use He4rt\Activity\Message\Models\Message;
use He4rt\Activity\Moderation\Models\ModerationEvent;
use He4rt\Activity\Retrospective\DiscordSource;
use He4rt\Activity\Timeline\Delegated\PostEntry;
use He4rt\Activity\Timeline\Listeners\PublishModerationToTimeline;
use He4rt\Activity\Timeline\Listeners\ReassignTimelineOwnership;
use He4rt\Activity\Timeline\Timeline;
use He4rt\Activity\Tracking\Listeners\ReassignInteractionOwnership;
use He4rt\Activity\Tracking\Listeners\TrackContentContribution;
use He4rt\Activity\Voice\Models\Voice;
use He4rt\Contents\Articles\Events\ArticlePublished;
use He4rt\Identity\Auth\Events\AccountsMerged;
use He4rt\Moderation\Enforcement\ActionExecuted;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class ActivityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Fonte da retrospectiva, descoberta pelo portal via tagged services.
        $this->app->tag([DiscordSource::class], 'retrospective.source');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        Relation::morphMap([
            'message' => Message::class,
            'voice' => Voice::class,
            'post_entry' => PostEntry::class,
            'moderation_event' => ModerationEvent::class,
            'timeline' => Timeline::class,
        ]);

        Event::listen(ActionExecuted::class, [PublishModerationToTimeline::class, 'handle']);
        Event::listen(AccountsMerged::class, [ReassignTimelineOwnership::class, 'handle']);
        Event::listen(AccountsMerged::class, [ReassignInteractionOwnership::class, 'handle']);
        Event::listen(ArticlePublished::class, [TrackContentContribution::class, 'handle']);
    }
}
