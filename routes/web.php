<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

if (env('APP_ENV') !== "production") {
    $router->get('swagger', function () {
        return view('swagger');
    });
}

$router->get('/auth/oauth/{provider}', 'AuthController@authenticate');
$router->get('/auth/logout', 'AuthController@logout');
