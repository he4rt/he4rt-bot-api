<?php

namespace Heart\User\Infrastructure\Providers;

use Heart\User\Presentation\Controllers\UsersController;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Route;

class UserRouteProvider extends RouteServiceProvider
{
    public function map(): void
    {
        Route::prefix('api')->middleware(['api', 'bot-auth'])->group(function () {
            Route::prefix('users')->group(function () {
                Route::get('/', [UsersController::class, 'getUsers'])->name('get-users');
                Route::get('/profile/{value}', [UsersController::class, 'getProfile'])->name('users.profile');
                Route::put('/profile/{value}', [UsersController::class, 'putProfile'])
                    ->name('users.profile.update');
                Route::get('/{id}', [UsersController::class, 'getUser'])->name('get-user');
            });
        });
    }
}
