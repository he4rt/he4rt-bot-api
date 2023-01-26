<?php

namespace Tests\Unit\Character\Application;

use Heart\Character\Application\ClaimDailyBonus;
use Heart\Character\Domain\Actions\PersistDailyBonus;
use Heart\Provider\Domain\Entities\ProviderEntity;
use Mockery\MockInterface;
use Mockery as m;
use Heart\Character\Application\FindCharacterIdByUserId;
use Heart\Provider\Application\FindProvider;
use Tests\TestCase;
use Tests\Unit\Character\ProviderProviderTrait;

class ClaimDailyBonusTest extends TestCase
{
    use ProviderProviderTrait;

    private MockInterface $persistDailyStub;

    private MockInterface $findProviderStub;

    private MockInterface $findCharacterIdByUserId;

    private ProviderEntity $providerEntity;

    public function setUp(): void
    {
        parent::setUp();
        $this->persistDailyStub = m::mock(PersistDailyBonus::class);
        $this->findProviderStub = m::mock(FindProvider::class);
        $this->findCharacterIdByUserId = m::mock(FindCharacterIdByUserId::class);
        $this->providerEntity = $this->validProviderEntity();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        m::close();
    }

    public function testClaimDailyBonusSuccess(): void
    {
        $this->findProviderStub
            ->shouldReceive('handle')
            ->with('canhassi-provider', 'canhassi-id')
            ->once()
            ->andReturn($this->providerEntity);

        $this->findCharacterIdByUserId
            ->shouldReceive('handle')
            ->with($this->providerEntity->userId)
            ->once()
            ->andReturn('character-id');

        $this->persistDailyStub
            ->shouldReceive('handle')
            ->with('character-id')
            ->once();

        $test = new ClaimDailyBonus(
            $this->persistDailyStub,
            $this->findProviderStub,
            $this->findCharacterIdByUserId
        );

        $test->handle('canhassi-provider', 'canhassi-id');
    }
}
