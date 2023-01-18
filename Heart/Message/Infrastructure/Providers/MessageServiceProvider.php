<?php

namespace Heart\Message\Infrastructure\Providers;

use Heart\Message\Domain\Repositories\MessageRepository;
use Heart\Message\Infrastructure\Repositories\MessageEloquentRepository;
use Illuminate\Support\ServiceProvider;

class MessageServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(MessageRepository::class, MessageEloquentRepository::class);
    }
}
