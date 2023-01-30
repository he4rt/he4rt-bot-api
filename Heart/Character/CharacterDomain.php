<?php

namespace Heart\Character;

use Heart\Character\Infrastructure\Providers\CharacterRouteProvider;
use Heart\Character\Infrastructure\Providers\CharacterServiceProvider;
use Heart\Core\Contracts\DomainInterface;

class CharacterDomain extends DomainInterface
{
    public function registerProvider(): array
    {
        return [
            CharacterServiceProvider::class,
            CharacterRouteProvider::class,
        ];
    }
}
