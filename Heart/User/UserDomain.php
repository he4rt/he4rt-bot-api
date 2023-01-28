<?php

namespace Heart\User;

use Heart\Core\Contracts\DomainInterface;
use Heart\User\Infrastructure\Providers\UserRouteProvider;
use Heart\User\Infrastructure\Providers\UserServiceProvider;

class UserDomain extends DomainInterface
{
    public function registerProvider(): array
    {
        return [
            UserServiceProvider::class,
            UserRouteProvider::class
        ];
    }
}
