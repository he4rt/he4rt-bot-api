<?php

namespace Heart\Integrations;

use Heart\Core\Contracts\DomainInterface;
use Heart\Integrations\Twitch\Common\TwitchIntegrationProvider;

class IntegrationsDomain extends DomainInterface
{
    public function registerProvider(): array
    {
        return [
            TwitchIntegrationProvider::class
        ];
    }
}
