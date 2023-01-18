<?php

namespace Heart\Meeting\Infrastructure\Providers;

use Heart\Character\Domain\Repositories\CharacterRepository;
use Heart\Character\Infrastructure\Repositories\CharacterEloquentRepository;
use Illuminate\Support\ServiceProvider;

class MeetingServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(CharacterRepository::class, CharacterEloquentRepository::class);
    }
}
