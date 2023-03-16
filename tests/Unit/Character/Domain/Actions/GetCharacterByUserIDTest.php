<?php

namespace Tests\Unit\Character\Domain\Actions;

use Heart\Character\Domain\Actions\GetCharacterByUserId;
use Heart\Character\Domain\Entities\CharacterEntity;
use Heart\Character\Domain\Repositories\CharacterRepository;
use Mockery as m;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Unit\Character\CharacterProviderTrait;

class GetCharacterByUserIDTest extends TestCase
{
    use CharacterProviderTrait;

    private MockInterface $characterRepositoryStub;

    private CharacterEntity $characterEntity;

    public function setUp(): void
    {
        parent::setUp();
        $this->characterRepositoryStub = m::mock(CharacterRepository::class);
        $this->characterEntity = $this->validCharacterEntity();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        m::close();
    }

    public function testGetCharacterByUserId(): void
    {
        $this->characterRepositoryStub
            ->shouldReceive('findByUserId')
            ->with(12)
            ->once()
            ->andReturn($this->characterEntity);

        $test = new GetCharacterByUserId($this->characterRepositoryStub);

        $test->handle(12);
    }
}
