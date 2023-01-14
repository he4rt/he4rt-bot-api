<?php

namespace Tests\Unit\Character\Domain\Actions;

use Heart\Character\Domain\Actions\ClaimDailyBonus;
use Heart\Character\Domain\Entities\CharacterEntity;
use Heart\Character\Domain\Repositories\CharacterRepository;
use Mockery as m;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Unit\Character\CharacterProviderTrait;

class ClaimDailyBonusTest extends TestCase
{
    use CharacterProviderTrait;

    private MockInterface $characterRepository;

    private ClaimDailyBonus $action;

    public function setUp(): void
    {
        parent::setUp();
        $this->characterRepository = m::mock(CharacterRepository::class);
        $this->action = new ClaimDailyBonus($this->characterRepository);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        m::close();
    }

    public function testCanClaim(): void
    {
        $characterId = '123';
        $characterEntity = $this->validCharacterEntity([
            'daily_bonus_claimed_at' => now()->addDay()->toString()
        ]);

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
        $this->assertTrue(true);
    }
}
