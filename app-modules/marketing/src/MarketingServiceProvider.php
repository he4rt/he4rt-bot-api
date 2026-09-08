<?php

declare(strict_types=1);

namespace He4rt\Marketing;

use Illuminate\Support\ServiceProvider;

class MarketingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'marketing');
    }
}
