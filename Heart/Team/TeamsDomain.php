<?php

namespace Heart\Team;

use Heart\Core\Contracts\DomainInterface;
use Heart\Team\Infrastructure\Providers\TeamRouteProvider;
use Heart\Team\Infrastructure\Providers\TeamServiceProvider;

class TeamsDomain extends DomainInterface
{
    public function registerProvider(): array
    {
        return [
            TeamServiceProvider::class,
            TeamRouteProvider::class,
        ];
    }
}
