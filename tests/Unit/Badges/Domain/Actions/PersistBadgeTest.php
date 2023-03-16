<?php

namespace Tests\Unit\Badges\Domain\Actions;

use Heart\Badges\Domain\Actions\PersistBadge;
use Heart\Badges\Domain\DTOs\NewBadgeDTO;
use Heart\Badges\Domain\Entities\BadgeEntity;
use Heart\Badges\Domain\Repositories\BadgeRepository;
use Mockery as m;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Unit\Badges\BadgeProviderTrait;

class PersistBadgeTest extends TestCase
{
    use BadgeProviderTrait;

    private MockInterface $badgeRepositoryStub;

    private BadgeEntity $badgeEntity;

    private NewBadgeDTO $badgeDTO;

    public function setUp(): void
    {
        parent::setUp();
        $this->badgeRepositoryStub = m::mock(BadgeRepository::class);
        $this->badgeEntity = $this->validBadgeEntity();
        $this->badgeDTO = new NewBadgeDTO(
            'canhassi', // provider
            $this->badgeEntity->name,
            $this->badgeEntity->description,
            'https://canhassi.tech', // image URL
            $this->badgeEntity->redeemCode,
            $this->badgeEntity->active
        );
    }

    public function tearDown(): void
    {
        parent::tearDown();
        m::close();
    }

    public function testPersistBadgeSuccess(): void
    {
        $this->badgeRepositoryStub
            ->shouldReceive('create')
            ->with($this->badgeDTO)
            ->once()
            ->andReturn($this->badgeEntity);

        $test = new PersistBadge($this->badgeRepositoryStub);

        $test->handle($this->badgeDTO);
    }
}
