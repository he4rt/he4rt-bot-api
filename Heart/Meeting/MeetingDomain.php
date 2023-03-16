<?php

namespace Heart\Meeting;

use Heart\Core\Contracts\DomainInterface;
use Heart\Meeting\Infrastructure\Providers\MeetingRouteProvider;
use Heart\Meeting\Infrastructure\Providers\MeetingServiceProvider;

class MeetingDomain extends DomainInterface
{
    public function registerProvider(): array
    {
        return [
            MeetingServiceProvider::class,
            MeetingRouteProvider::class,
        ];
    }
}
