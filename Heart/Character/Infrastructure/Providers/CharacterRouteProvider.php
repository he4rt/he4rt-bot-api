<?php

namespace Heart\Character\Infrastructure\Providers;

use Heart\Character\Presentation\Controllers\CharactersController;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Route;

class CharacterRouteProvider extends RouteServiceProvider
{
    public function map()
    {
        Route::get('/characters', [CharactersController::class, 'getCharacters']);
    }
}
