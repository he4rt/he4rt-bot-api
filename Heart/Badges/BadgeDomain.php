<?php

namespace Heart\Badges;

use Heart\Badges\Infrastructure\Providers\BadgeRouteProvider;
use Heart\Badges\Infrastructure\Providers\BadgeServiceProvider;
use Heart\Core\Contracts\DomainInterface;

class BadgeDomain extends DomainInterface
{
    public function registerProvider(): array
    {
        return [
            BadgeServiceProvider::class,
            BadgeRouteProvider::class,
        ];
    }
}
