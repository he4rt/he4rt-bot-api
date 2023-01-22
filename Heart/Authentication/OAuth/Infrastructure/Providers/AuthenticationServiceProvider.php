<?php

namespace Heart\Authentication\OAuth\Infrastructure\Providers;

use Heart\Authentication\OAuth\Domain\Repositories\ProviderRepository;
use Heart\Authentication\OAuth\Domain\Repositories\TokenRepository;
use Heart\Authentication\OAuth\Infrastructure\Repositories\ProviderEloquentRepository;
use Heart\Authentication\OAuth\Infrastructure\Repositories\TokenEloquentRepository;
use Illuminate\Support\ServiceProvider;

class AuthenticationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProviderRepository::class, ProviderEloquentRepository::class);
        $this->app->bind(TokenRepository::class, TokenEloquentRepository::class);
    }
}
