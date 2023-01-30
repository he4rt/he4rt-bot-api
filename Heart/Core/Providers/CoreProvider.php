<?php

namespace Heart\Core\Providers;

use Heart\Core\Classes\DomainManager;
use Illuminate\Support\ServiceProvider;

class CoreProvider extends ServiceProvider
{
    public function register()
    {
        $providers = DomainManager::instance()->getproviders();
        foreach ($providers as $provider) {
            $this->app->register($provider);
        }
    }
}
