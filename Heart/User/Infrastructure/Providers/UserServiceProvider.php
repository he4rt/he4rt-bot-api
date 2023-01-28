<?php

namespace Heart\User\Infrastructure\Providers;

use Heart\User\Domain\Repositories\UserRepository;
use Heart\User\Infrastructure\Repositories\UserEloquentRepository;
use Illuminate\Support\ServiceProvider;

class UserServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(UserRepository::class, UserEloquentRepository::class);
    }
}
