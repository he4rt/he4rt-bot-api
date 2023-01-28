<?php

namespace Heart\Feedback\Infrastructure\Providers;

use Heart\Feedback\Presentation\Controllers\FeedbacksController;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Route;

class FeedbackRouteProvider extends RouteServiceProvider
{
    public function map()
    {
        Route::post('/v2/feedbacks', [FeedbacksController::class, 'create'])->name('feedbacks.create');
    }
}
