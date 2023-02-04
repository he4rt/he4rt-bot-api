<?php

namespace Heart\Ranking\Infrastructure\Providers;

use Heart\Ranking\Domain\Repositories\RankingRepository;
use Heart\Ranking\Infrastructure\Repositories\RankingEloquentRepository;
use Illuminate\Support\ServiceProvider;

class RankingServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(RankingRepository::class, RankingEloquentRepository::class);
    }
}
