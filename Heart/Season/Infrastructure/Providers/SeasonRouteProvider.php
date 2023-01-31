<?php

namespace Heart\Season\Infrastructure\Providers;

use Heart\Season\Presentation\Controllers\SeasonsController;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Route;

class SeasonRouteProvider extends RouteServiceProvider
{
    public function map()
    {
        Route::prefix('season')->group(function () {
            Route::get('/v2/seasons', [SeasonsController::class, 'getSeasons'])->name('get-seasons');
            Route::get('/v2/seasons/current', [SeasonsController::class , 'getCurrent'])
                ->name('seasons.current');
        });
    }
}
