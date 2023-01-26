<?php

namespace Tests\Unit\Badges\Application;

use Heart\Badges\Application\FindBadgeBySlug;
use Heart\Badges\Domain\Entities\BadgeEntity;
use Heart\Badges\Domain\Repositories\BadgeRepository;
use Mockery\MockInterface;
use Mockery as m;
use Tests\TestCase;
use Tests\Unit\Badges\BadgeProviderTrait;

class FindBadgeBySlugTest extends TestCase
{
    use BadgeProviderTrait;

    private MockInterface $badgeRepositoryStub;

    private BadgeEntity $badgeEntity;

    public function setUp(): void
    {
        parent::setUp();
        $this->badgeRepositoryStub = m::mock(BadgeRepository::class);
        $this->badgeEntity = $this->validBadgeEntity();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        m::close();
    }

    public function testFindBadgeBySlug(): void
    {
        $slug = 'é-o-canhas';
        $this->badgeRepositoryStub
            ->shouldReceive('findBySlug')
            ->with($slug)
            ->once()
            ->andReturn($this->badgeEntity);

        $test = new FindBadgeBySlug($this->badgeRepositoryStub);

        $test->handle($slug);
    }
}
