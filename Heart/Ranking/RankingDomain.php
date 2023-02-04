<?php

namespace Heart\Ranking;

use Heart\Core\Contracts\DomainInterface;
use Heart\Ranking\Infrastructure\Providers\RankingRouteProvider;
use Heart\Ranking\Infrastructure\Providers\RankingServiceProvider;

class RankingDomain extends DomainInterface
{
    public function registerProvider(): array
    {
        return [
            RankingServiceProvider::class,
            RankingRouteProvider::class,
        ];
    }
}
