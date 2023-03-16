<?php

namespace Tests\Unit\Character\Domain\Actions;

use Heart\Character\Domain\Actions\PaginateCharacters;
use Heart\Character\Domain\Repositories\CharacterRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Mockery as m;
use Mockery\MockInterface;
use Tests\TestCase;

class PaginateCharactersTest extends TestCase
{
    private MockInterface $characterRepository;

    private PaginateCharacters $paginateCharactersAction;

    protected function setUp(): void
    {
        parent::setUp();
        $this->characterRepository = m::mock(CharacterRepository::class);
        $this->paginateCharactersAction = new PaginateCharacters($this->characterRepository);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        m::close();
    }

    public function testCanPaginate(): void
    {
        $this->characterRepository
            ->shouldReceive('paginate')
            ->once()
            ->andReturn(m::mock(LengthAwarePaginator::class));

        $result = $this->paginateCharactersAction->handle();

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }
}
