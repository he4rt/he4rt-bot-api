<?php

namespace Heart\Authentication\OAuth\Infrastructure\Providers;

use Heart\Authentication\OAuth\Presentation\Controllers\OAuthController;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Route;

class AuthenticationRouteProvider extends RouteServiceProvider
{
    public function map(): void
    {
        Route::prefix('auth')
            ->middleware('web')
            ->group(function () {
                Route::prefix('oauth')->group(function () {
                    Route::get('/{provider}', [OAuthController::class, 'getAuthenticate']);
                    Route::get('/{provider}/redirect', [OAuthController::class, 'getRedirect']);
                });
            });
    }
}
