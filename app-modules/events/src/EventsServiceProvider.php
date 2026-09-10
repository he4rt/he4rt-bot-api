<?php

declare(strict_types=1);

namespace He4rt\Events;

use He4rt\Events\CheckIn\Events\CheckInRequested;
use He4rt\Events\CheckIn\Listeners\GenerateQrTokenOnConfirmed;
use He4rt\Events\CheckIn\Listeners\HandleBotCheckIn;
use He4rt\Events\Console\Commands\ClosePendingEventsCommand;
use He4rt\Events\Enrollment\Events\EnrollmentConfirmed;
use He4rt\Events\Gallery\Models\Album;
use He4rt\Events\Event\Models\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event as EventFacade;
use Illuminate\Support\ServiceProvider;

class EventsServiceProvider extends ServiceProvider
{
    /**
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'events');

        Relation::morphMap([
            'events_album' => Album::class,
            'event' => Event::class,
        ]);
        
        EventFacade::listen(EnrollmentConfirmed::class, GenerateQrTokenOnConfirmed::class);
        EventFacade::listen(CheckInRequested::class, HandleBotCheckIn::class);

        if ($this->app->runningInConsole()) {
            $this->app->make(Schedule::class)
                ->command(ClosePendingEventsCommand::class)
                ->everyFifteenMinutes()
                ->withoutOverlapping();
        }
    }
}
