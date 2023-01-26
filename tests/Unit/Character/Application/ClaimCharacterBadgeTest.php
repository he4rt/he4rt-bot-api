<?php

namespace Tests\Unit\Character\Application;

use Heart\Badges\Application\FindBadgeBySlug;
use Heart\Badges\Domain\Entities\BadgeEntity;
use Heart\Character\Application\ClaimCharacterBadge;
use Heart\Character\Application\FindCharacterIdByUserId;
use Heart\Character\Domain\Actions\PersistClaimedBadge;
use Heart\Provider\Application\FindProvider;
use Heart\Provider\Domain\Entities\ProviderEntity;
use Mockery as m;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Unit\Character\BadgeProviderTrait;
use Tests\Unit\Character\ProviderProviderTrait;

class ClaimCharacterBadgeTest extends TestCase
{
    use ProviderProviderTrait;

    use BadgeProviderTrait;

    private MockInterface $persistClaimBadgeStub;

    private MockInterface $findProviderStub;

    private MockInterface $findCharacterIdByUserId;

    private MockInterface $findBadgeBySlug;

    private ProviderEntity $providerEntity;

    private BadgeEntity $badgeEntity;

    public function setUp(): void
    {
        parent::setUp();
        $this->persistClaimBadgeStub = m::mock(PersistClaimedBadge::class);
        $this->findProviderStub = m::mock(FindProvider::class);
        $this->findCharacterIdByUserId = m::mock(FindCharacterIdByUserId::class);
        $this->findBadgeBySlug = m::mock(FindBadgeBySlug::class);
        $this->providerEntity = $this->validProviderEntity();
        $this->badgeEntity = $this->validBadgeEntity();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        m::close();
    }

    public function testClaimCharacterBadgeSuccess(): void
    {
        $this->findProviderStub
            ->shouldReceive('handle')
            ->with("canhassi-provider", "canhassi-id")
            ->once()
            ->andReturn($this->providerEntity);

        $this->findCharacterIdByUserId
            ->shouldReceive('handle')
            ->with($this->providerEntity->userId)
            ->once()
            ->andReturn('character-id');

        $this->findBadgeBySlug
            ->shouldReceive('handle')
            ->with('é o canhas')
            ->once()
            ->andReturn($this->badgeEntity);

        $this->persistClaimBadgeStub
            ->shouldReceive('handle')
            ->with('character-id', $this->badgeEntity->id)
            ->once();

        $test = new ClaimCharacterBadge(
            $this->persistClaimBadgeStub,
            $this->findProviderStub,
            $this->findCharacterIdByUserId,
            $this->findBadgeBySlug
        );

        $test->handle('canhassi-provider', 'canhassi-id', 'é o canhas');
    }
}
