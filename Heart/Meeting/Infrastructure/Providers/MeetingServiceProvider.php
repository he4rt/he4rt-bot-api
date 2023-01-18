<?php

namespace Heart\Meeting\Infrastructure\Providers;

use Heart\Character\Domain\Repositories\CharacterRepository;
use Heart\Character\Infrastructure\Repositories\CharacterEloquentRepository;
use Heart\Meeting\Domain\Repositories\MeetingRepository;
use Heart\Meeting\Infrastructure\Repositories\MeetingEloquentRepository;
use Illuminate\Support\ServiceProvider;

class MeetingServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(MeetingRepository::class, MeetingEloquentRepository::class);
    }
}
