<?php

namespace Tests\Unit\Providers\Application;

use Heart\Provider\Application\FindProvider;
use Heart\Provider\Domain\Actions\GetProviderById;
use Heart\Provider\Domain\Entities\ProviderEntity;
use Heart\Shared\Application\TTL;
use Illuminate\Support\Facades\Cache;
use Mockery as m;
use Tests\TestCase;

class FindProviderTest extends TestCase
{
    public function testCachedProvider()
    {
        $cacheKey = 'provider-twitch-123';
        $getProviderStub = m::mock(GetProviderById::class);

        Cache::shouldReceive('remember')
            ->once()
            ->with($cacheKey, TTL::fromDays(2), m::type('closure'))
            ->andReturn(new ProviderEntity(1, 1, 1, 1, '1'));

        $action = new FindProvider($getProviderStub);

        $result = $action->handle('twitch', '123');

        $this->assertInstanceOf(ProviderEntity::class, $result);
    }
}
