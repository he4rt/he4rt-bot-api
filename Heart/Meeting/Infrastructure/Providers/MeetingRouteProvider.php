<?php

namespace Heart\Meeting\Infrastructure\Providers;

use Heart\Meeting\Presentation\Controllers\MeetingController;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Route;

class MeetingRouteProvider extends RouteServiceProvider
{
    public function map()
    {
        Route::prefix('events/{provider}')->name('events.')
            ->middleware('bot-auth')
            ->group(function () {
                Route::prefix('meeting')->name('meeting.')->group(function () {
                    Route::get('/', [MeetingController::class, 'getMeetings'])->name('getMeetings');
                    Route::post('/start', [MeetingController::class, 'postMeeting'])->name('postMeeting');
                    Route::post('/end', [MeetingController::class, 'postEndMeeting'])->name('postEndMeeting');
                });
            });
    }
}
