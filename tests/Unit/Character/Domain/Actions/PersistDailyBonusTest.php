<?php

namespace Tests\Unit\Character\Domain\Actions;

use Carbon\Carbon;
use Heart\Character\Domain\Actions\PersistDailyBonus;
use Heart\Character\Domain\Exceptions\CharacterException;
use Heart\Character\Domain\Repositories\CharacterRepository;
use Mockery as m;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Unit\Character\CharacterProviderTrait;

class PersistDailyBonusTest extends TestCase
{
    use CharacterProviderTrait;

    private MockInterface $characterRepository;

    private PersistDailyBonus $action;

    public function setUp(): void
    {
        parent::setUp();
        $this->characterRepository = m::mock(CharacterRepository::class);
        $this->action = new PersistDailyBonus($this->characterRepository);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        m::close();
    }

    public function testCanClaim(): void
    {
        $characterId = '123';
        Carbon::setTestNow(now()->subMinute());
        $characterEntity = $this->validCharacterEntity();
        Carbon::setTestNow(now()->addDay()->addMinute());

        $this->characterRepository
            ->shouldReceive('findById')
            ->with($characterId)
            ->once()
            ->andReturn($characterEntity);

        $this->characterRepository
            ->shouldReceive('claimDailyBonus')
            ->with($characterEntity)
            ->once();

        $this->action->handle($characterId);
    }

    public function testShouldNotClaim(): void
    {
        $this->expectException(CharacterException::class);

        $characterId = '123';
        $characterEntity = $this->validCharacterEntity();

        $this->characterRepository
            ->shouldReceive('findById')
            ->with($characterId)
            ->once()
            ->andReturn($characterEntity);

        $this->action->handle($characterId);
    }
}
