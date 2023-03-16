<?php

namespace Heart\Feedback\Infrastructure\Providers;

use Heart\Feedback\Domain\Repositories\FeedbackRepository;
use Heart\Feedback\Infrastructure\Repositories\FeedbackEloquentRepository;
use Illuminate\Support\ServiceProvider;

class FeedbackServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(FeedbackRepository::class, FeedbackEloquentRepository::class);
    }
}
