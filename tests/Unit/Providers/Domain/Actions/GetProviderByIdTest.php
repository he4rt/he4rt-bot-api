<?php

namespace Tests\Unit\Providers\Domain\Actions;

use Heart\Provider\Domain\Actions\GetProviderById;
use Heart\Provider\Domain\Entities\ProviderEntity;
use Heart\Provider\Domain\Repositories\ProviderRepository;
use Mockery as m;
use Tests\TestCase;

class GetProviderByIdTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testGetProviderById()
    {
        $providerRepositoryStub = m::mock(ProviderRepository::class);

        $providerRepositoryStub
            ->shouldReceive('findByProvider')
            ->once()
            ->with('twitch', '12345678')
            ->andReturn(new ProviderEntity(1, 1, 1, 1, 'email@foda.com'));

        $action = new GetProviderById($providerRepositoryStub);

        $result = $action->handle('twitch', '12345678');
        $this->assertInstanceOf(ProviderEntity::class, $result);
    }
}
