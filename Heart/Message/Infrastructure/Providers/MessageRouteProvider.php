<?php

namespace Heart\Message\Infrastructure\Providers;

use Heart\Message\Presentation\Controllers\MessagesController;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Route;

class MessageRouteProvider extends RouteServiceProvider
{
    public function map()
    {
        Route::prefix('messages')->group(function () {
            Route::post('/{provider}', [MessagesController::class, 'postMessage'])->name('messages.create');
        });
    }
}
