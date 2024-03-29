<?php

namespace Tests\Unit\User\Application;

use Heart\User\Application\GetUser;
use Heart\User\Domain\Entities\UserEntity;
use Heart\User\Domain\Repositories\UserRepository;
use Mockery as m;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Unit\User\UserProviderTrait;

class GetUserTest extends TestCase
{
    use UserProviderTrait;

    private MockInterface $repositoryStub;

    private UserEntity $userEntity;

    public function setUp(): void
    {
        parent::setUp();
        $this->repositoryStub = m::mock(UserRepository::class);
        $this->userEntity = $this->validUserEntity();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        m::close();
    }

    public function testGetUser(): void
    {
        $this->repositoryStub
            ->shouldReceive('find')
            ->with('12')
            ->once()
            ->andReturn($this->userEntity);

        $test = new GetUser($this->repositoryStub);

        $test->handle('12');
    }
}
