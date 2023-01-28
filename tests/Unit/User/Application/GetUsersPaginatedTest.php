<?php

namespace Tests\Unit\User\Domain\Application;

use Heart\Shared\Domain\Paginator;
use Heart\User\Application\GetUsersPaginated;
use Heart\User\Domain\Repositories\UserRepository;
use Mockery\MockInterface;
use Mockery as m;
use Tests\TestCase;

class GetUsersPaginatedTest extends TestCase
{
    private MockInterface $repositoryStub;

    private MockInterface $paginatorStub;

    public function setUp(): void
    {
        parent::setUp();
        $this->repositoryStub = m::mock(UserRepository::class);
        $this->paginatorStub = m::mock(Paginator::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        m::close();
    }

    public function testGetUsersPaginated(): void
    {
        $this->repositoryStub
            ->shouldReceive('paginated')
            ->once()
            ->andReturn($this->paginatorStub);

        $test = new GetUsersPaginated($this->repositoryStub);

        $test->handle();
    }
}
