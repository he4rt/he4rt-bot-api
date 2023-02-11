<?php

namespace Heart\Message\Infrastructure\Providers;

use Heart\Message\Domain\Repositories\MessageRepository;
use Heart\Message\Domain\Repositories\VoiceRepository;
use Heart\Message\Infrastructure\Repositories\MessageEloquentRepository;
use Heart\Message\Infrastructure\Repositories\VoiceEloquentRepository;
use Illuminate\Support\ServiceProvider;

class MessageServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(MessageRepository::class, MessageEloquentRepository::class);
        $this->app->bind(VoiceRepository::class, VoiceEloquentRepository::class);
    }
}
