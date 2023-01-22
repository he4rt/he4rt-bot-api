<?php

namespace Heart\Badges\Infrastructure\Providers;

use Heart\Badges\Presentation\Controllers\BadgesController;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Route;

class BadgeRouteProvider extends RouteServiceProvider
{
    public function map()
    {
        Route::middleware('bot-auth')->prefix('v2/badges')->group(function () {
            Route::get('/', [BadgesController::class, 'getBadges'])->name('badges.index');
            Route::post('/', [BadgesController::class, 'postBadge'])->name('badges.store');
            Route::delete('/{badgeId}', [BadgesController::class, 'deleteBadge'])->name('badges.destroy');
        });
    }
}
