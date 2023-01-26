<?php

namespace Heart\User\Infrastructure\Providers;

use Heart\User\Presentation\UsersController;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Route;

class UserRouteProvider extends RouteServiceProvider
{
    public function map(): void
    {
        Route::prefix('users')->middleware('bot-auth')->group(function () {
            Route::get('/', [UsersController::class, 'getUsers'])->name('get-users');
            Route::get('/profile/{value}', [UsersController::class, 'getProfile'])->name('users.profile');
            Route::get('/{id}', [UsersController::class, 'getUser'])->name('get-user');
            Route::post('/', [UsersController::class, 'postUser'])->name('post-user');
        });
    }
}
