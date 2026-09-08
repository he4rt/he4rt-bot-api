<?php

declare(strict_types=1);

namespace He4rt\Identity;

use He4rt\Identity\Authorization\Console\GrantSuperAdmin;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class IdentityServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        Relation::morphMap([
            'user' => User::class,
        ]);

        Gate::before(fn (User $user, string $ability): ?bool => $user->isSuperAdmin() ? true : null);

        if ($this->app->runningInConsole()) {
            $this->commands([GrantSuperAdmin::class]);
        }
    }
}
