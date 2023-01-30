<?php

namespace Tests\Unit\Character\Application;

use Heart\Character\Application\FindCharacterIdByUserId;
use Heart\Character\Domain\Actions\GetCharacterByUserId;
use Heart\Character\Domain\Entities\CharacterEntity;
use Mockery as m;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Unit\Character\CharacterProviderTrait;

class FindCharacterIdByUserIdTest extends TestCase
{
    use CharacterProviderTrait;

    private MockInterface $getCharacterIdByUserId;

    private CharacterEntity $characterEntity;

    public function setUp(): void
    {
        parent::setUp();
        $this->getCharacterIdByUserId = m::mock(GetCharacterByUserId::class);
        $this->characterEntity = $this->validCharacterEntity();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        m::close();
    }

    public function testFindCharacterByUserId(): void
    {
        $this->getCharacterIdByUserId
            ->shouldReceive('handle')
            ->with('canhassi-id')
            ->once()
            ->andReturn($this->characterEntity);

        $test = new FindCharacterIdByUserId($this->getCharacterIdByUserId);

        $test->handle('canhassi-id');
    }
}
