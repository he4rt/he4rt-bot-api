<?php

namespace Heart\User\Infrastructure\Providers;

use App\Providers\RouteServiceProvider;
use Heart\User\Presentation\UsersController;
use Illuminate\Support\Facades\Route;

class UserRouteProvider extends RouteServiceProvider
{
    public function map(): void
    {
        Route::prefix('users')->middleware('bot-auth')->name('users.')->group(function () {
            Route::get('/', UsersController::class . 'getUsers')->name('get-users');
            Route::get('/{id}', UsersController::class . 'getUser')->name('get-user');
            Route::post('/', UsersController::class . 'postUser')->name('post-user');
        });
    }
}
