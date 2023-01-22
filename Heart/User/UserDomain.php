<?php

namespace Heart\User;

use Heart\Core\Contracts\DomainInterface;
use Heart\User\Infrastructure\Providers\UserRouteProvider;

class UserDomain extends DomainInterface
{
    public function registerProvider(): array
    {
        return [
            UserRouteProvider::class
        ];
    }
}
