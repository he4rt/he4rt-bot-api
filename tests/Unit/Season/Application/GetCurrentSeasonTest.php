<?php

namespace Tests\Unit\Season\Application;

use Heart\Season\Application\GetCurrentSeason;
use Heart\Season\Domain\Entities\SeasonEntity;
use Heart\Season\Domain\Repositories\SeasonRepository;
use Mockery as m;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Unit\Season\SeasonProviderTrait;

class GetCurrentSeasonTest extends TestCase
{
    use SeasonProviderTrait;

    private MockInterface $seasonRepositoryStub;

    private SeasonEntity $seasonEntity;

    public function setUp(): void
    {
        parent::setUp();
        $this->seasonRepositoryStub = m::mock(SeasonRepository::class);
        $this->seasonEntity = $this->validSeasonEntity();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        m::close();
    }

    public function testGetCurrentSeason(): void
    {
        $this->seasonRepositoryStub
            ->shouldReceive('getCurrent')
            ->once()
            ->andReturn($this->seasonEntity);

        $test = new GetCurrentSeason($this->seasonRepositoryStub);

        $test->handle();
    }
}
