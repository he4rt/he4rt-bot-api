<?php

namespace Tests\Unit\Character\Domain\Actions;

use Heart\Character\Domain\Actions\PersistDailyBonus;
use Heart\Character\Domain\Actions\ManageReputation;
use Heart\Character\Domain\Entities\CharacterEntity;
use Heart\Character\Domain\Repositories\CharacterRepository;
use Mockery as m;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Unit\Character\CharacterProviderTrait;

class ManageReputationTest extends TestCase
{
    use CharacterProviderTrait;

    private ManageReputation $manageReputation;
    private MockInterface $characterRepository;
    private PersistDailyBonus $claimDailyBonus;

    public function setUp(): void
    {
        parent::setUp();
        $this->characterRepository = m::mock(CharacterRepository::class);
        $this->manageReputation = new ManageReputation($this->characterRepository);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        m::close();
    }
    public function testAddReputation()
    {
        $character = $this->validCharacterEntity();
        $characterId = 'porra-careca';

        $this->characterRepository
            ->shouldReceive('findById')
            ->once()
            ->with($characterId)
            ->andReturn($character);

        $this->characterRepository
            ->shouldReceive('updateReputation')
            ->once()
            ->with($character);

        $this->manageReputation->handle($characterId, 'increment');
    }
}
