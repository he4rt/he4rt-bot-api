<?php

declare(strict_types=1);

namespace App\Providers\Tools;

use He4rt\Identity\User\Models\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerTelescopeServiceProvider();
        $this->setNightMode();
        $this->hideSensitiveRequestDetails();
        $this->setFilter();
    }

    public function boot(): void
    {
        if (!$this->canBoot()) {
            return;
        }

        parent::boot();
    }

    public function canBoot(): bool
    {
        return config('telescope.enabled');
    }

    /**
     * Register the Telescope gate.
     *
     * This gate determines who can access Telescope in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewTelescope', fn (User $user) => $user->isSuperAdmin());
    }

    /**
     * Prevent sensitive request details from being logged by Telescope.
     */
    protected function hideSensitiveRequestDetails(): void
    {
        Telescope::hideRequestParameters(['_token']);
        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    private function registerTelescopeServiceProvider(): void
    {
        $this->app->register(provider: \Laravel\Telescope\TelescopeServiceProvider::class);
    }

    private function setNightMode(): void
    {
        Telescope::night();
    }

    private function setFilter(): void
    {
        Telescope::filter(fn (): bool => true);
        Telescope::filterBatch(fn (): bool => true);
    }
}
