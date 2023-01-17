<?php

namespace Heart\Character\Infrastructure\Providers;

use Heart\Character\Domain\Repositories\CharacterRepository;
use Heart\Character\Infrastructure\Repositories\CharacterEloquentRepository;
use Illuminate\Support\ServiceProvider;

class CharacterServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(CharacterRepository::class, CharacterEloquentRepository::class);
    }
}
