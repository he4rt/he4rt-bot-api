<?php

namespace Heart\Provider\Infrastructure\Providers;

use Heart\Provider\Domain\Repositories\ProviderRepository;
use Heart\Provider\Infrastructure\Repositories\ProviderEloquentRepository;
use Illuminate\Support\ServiceProvider;

class ProviderServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(ProviderRepository::class, ProviderEloquentRepository::class);
    }
}
