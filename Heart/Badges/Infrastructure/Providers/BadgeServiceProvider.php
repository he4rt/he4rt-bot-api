<?php

namespace Heart\Badges\Infrastructure\Providers;

use Heart\Badges\Domain\Repositories\BadgeRepository;
use Heart\Badges\Infrastructure\Repositories\BadgeEloquentRepository;
use Illuminate\Support\ServiceProvider;

class BadgeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(BadgeRepository::class, BadgeEloquentRepository::class);
    }
}
