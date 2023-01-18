<?php

namespace Heart\Meeting\Infrastructure\Providers;

use Heart\Meeting\Presentation\Controllers\MeetingController;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Route;

class MeetingRouteProvider extends RouteServiceProvider
{
    public function map()
    {
        Route::prefix('events')->name('events')
//            ->middleware('bot-auth')
            ->group(function () {

            Route::prefix('meeting')->prefix('meeting')->group(function () {
                Route::get('/', [MeetingController::class , 'getMeetings'])->name('getMeetings');
//                Route::post('/', ['uses' => MeetingController::class . '@postMeeting', 'as' => 'events.meeting.postMeeting']);
//                Route::post('/end', ['uses' => MeetingController::class . '@postEndMeeting', 'as' => 'events.meeting.postEndMeeting']);
//                Route::post('/attend', ['uses' => MeetingController::class . '@postAttendMeeting', 'as' => 'events.meeting.postAttendMeeting']);
//                Route::patch(
//                    '/{meetingId}/subject',
//                    ['uses' => MeetingController::class . '@postMeetingSubject', 'as' => 'events.meeting.postMeetingSubject']
//                );
            });
        });
    }
}
