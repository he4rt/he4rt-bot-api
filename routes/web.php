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

$router->group(['prefix' => 'users', 'middleware' => 'bot-auth'], function ($router) {
    $router->get('/', 'Users\UsersController@getUsers');
    $router->post('/', 'Users\UsersController@postUser');
    $router->get('/{discordId}', 'Users\UsersController@getUser');
    $router->put('/{discordId}', 'Users\UsersController@putUser');
    $router->delete('/{discordId}', 'Users\UsersController@deleteUser');

    $router->post('/daily', 'Users\UsersController@postDaily');
});

$router->group(['prefix' => 'bot', 'middleware' => 'bot-auth'], function ($router) {
    $router->group(['prefix' => 'gambling'], function ($router) {
        $router->put('money', 'Gamification\GamblingController@putMoney');
    });
    $router->post('gamification/levelup', 'Gamification\LevelupController@postLevelUp');
});

$router->group(['prefix' => 'ranking'], function ($router) {
    $router->get('general', 'Gamification\RankingController@getGeneralLevelRanking');
    $router->get('messages', 'Gamification\RankingController@getGeneralMessageRanking');
});

$router->group(['prefix' => 'badges'], function ($router) {
    $router->get('/', 'Gamification\BadgeController@getBadges');
    $router->post('/', 'Gamification\BadgeController@postBadge');
    $router->get('/{badgeId}', 'Gamification\BadgeController@getBadge');
    $router->delete('/{badgeId}', 'Gamification\BadgeController@deleteBadge');
});
