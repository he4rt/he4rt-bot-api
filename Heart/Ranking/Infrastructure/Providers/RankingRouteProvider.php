<?php

namespace Heart\Ranking\Infrastructure\Providers;

use Heart\Ranking\Domain\Actions\RankingByLevel;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Route;

class RankingRouteProvider extends RouteServiceProvider
{
    public function map()
    {
        Route::middleware('api')
            ->prefix('api')
            ->group(function () {
                Route::get('/ranking/leveling', [RankingByLevel::class, 'handle'])->name('ranking.leveling');
            });
    }
}
