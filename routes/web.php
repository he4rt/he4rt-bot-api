<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Events\BadgesController;
use App\Http\Controllers\Events\MeetingsController;
use App\Http\Controllers\Feedbacks\FeedbackController;
use App\Http\Controllers\Feedbacks\FeedbackReviewController;
use App\Http\Controllers\Gamification\GamblingController;
use App\Http\Controllers\Gamification\RankingController;
use App\Http\Controllers\Gamification\RewardController;
use App\Http\Controllers\Users\UsersController;
use Laravel\Lumen\Routing\Router;

/** @var Router $router */

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

if (config('app.env') !== "production") {
    $router->get('swagger', function () {
        return view('swagger');
    });
}

$router->get('/auth/oauth/{provider}', AuthController::class.'@authenticate');
$router->get('/auth/logout', AuthController::class.'@logout');

$router->group(['prefix' => 'users', 'middleware' => 'bot-auth'], function (Router $router) {
    /*
    |--------------------------------------------------------------------------
    | Basic User Routes
    |--------------------------------------------------------------------------
    | Only for CRUD operations
    */

    $router->get('/', UsersController::class.'@getUsers');
    $router->post('/', ['uses' => UsersController::class.'@create', 'as' => 'users.store']);
    $router->get('/{discordId}', ['uses' => UsersController::class.'@getUser', 'as' => 'users.show']);
    $router->put('/{discordId}', ['uses' => UsersController::class.'@update', 'as' => 'users.update']);
    $router->delete('/{discordId}', ['uses' => UsersController::class.'@deleteUser', 'as' => 'users.destroy']);

    /*
    |--------------------------------------------------------------------------
    | Specific Gamefication Routes
    |--------------------------------------------------------------------------
    | For gamefication and other stuff
    */

    $router->post('/{discordId}/daily', ['uses' => UsersController::class.'@postDaily', 'as' => 'users.dailyPoints']);
    $router->post('/{discordId}/message', ['uses' => UsersController::class.'@postMessage', 'as' => 'users.messages.store']);
    $router->post('/{discordId}/claim-badge', ['uses' => BadgesController::class.'@postClaimBadge', 'as' => 'users.badges.claim']);
    $router->get('/{discordId}/voice', ['uses' => RewardController::class.'@claimVoiceXp', 'as' => 'users.voice.claim']);
});

$router->group(['prefix' => 'events', 'middleware' => 'bot-auth'], function (Router $router) {
    $router->group(['prefix' => 'badges'], function (Router $router) {
        $router->post('/', ['uses' => BadgesController::class.'@postBadge', 'as' => 'events.badges.store']);
    });

    $router->group(['prefix' => 'meeting'], function ($router) {
        $router->get('/', ['uses' => MeetingsController::class . '@getMeetings', 'as' => 'events.meeting.getMeetings']);
        $router->post('/', ['uses' => MeetingsController::class . '@postMeeting', 'as' => 'events.meeting.postMeeting']);
        $router->post('/end', ['uses' => MeetingsController::class . '@postEndMeeting', 'as' => 'events.meeting.postEndMeeting']);
        $router->post('/attend', ['uses' => MeetingsController::class . '@postAttendMeeting', 'as' => 'events.meeting.postAttendMeeting']);
        $router->patch(
            '/{meetingId}/subject',
            ['uses' => MeetingsController::class . '@postMeetingSubject', 'as' => 'events.meeting.postMeetingSubject']
        );
    });
});


$router->group(['prefix' => 'bot', 'middleware' => 'bot-auth'], function (Router $router) {
    $router->group(['prefix' => 'gambling'], function (Router $router) {
        $router->put('money', GamblingController::class.'@putMoney');
    });
});

$router->group(['prefix' => 'ranking'], function (Router $router) {
    $router->get('general', RankingController::class.'@getGeneralLevelRanking');
    $router->get('messages', RankingController::class.'@getGeneralMessageRanking');
});

$router->group(['prefix' => 'feedback', 'as' => 'feedback'], function (Router $router) {
    $router->post('/', ['uses' => FeedbackController::class.'@create', 'as' => 'create']);
    $router->post('/review/{feedbackId}/approve', ['uses' => FeedbackReviewController::class.'@approve', 'as' => 'review.approve']);
    $router->post('/review/{feedbackId}/decline', ['uses' => FeedbackReviewController::class.'@decline', 'as' => 'review.decline']);
});

if (config('features.gamification.badges')) {
    $router->group(['prefix' => 'badges'], function (Router $router) {
        $router->get('/', BadgesController::class.'@getBadges');
        $router->post('/', BadgesController::class.'@postBadge');
        $router->get('/{badgeId}', BadgesController::class.'@getBadge');
        $router->delete('/{badgeId}', BadgesController::class.'@deleteBadge');
    });
}
