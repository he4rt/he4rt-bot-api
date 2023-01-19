<?php

namespace Heart\Message;

use Heart\Core\Contracts\DomainInterface;
use Heart\Message\Infrastructure\Providers\MessageRouteProvider;
use Heart\Message\Infrastructure\Providers\MessageServiceProvider;

class MessageDomain extends DomainInterface
{

    public function registerProvider(): array
    {
        return [
            MessageServiceProvider::class,
            MessageRouteProvider::class
        ];
    }
}
