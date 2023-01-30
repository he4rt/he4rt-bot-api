<?php

namespace Tests\Unit\User\Application;

use Heart\Provider\Domain\Entities\ProviderEntity;
use Heart\Provider\Domain\Repositories\ProviderRepository;
use Heart\User\Application\Exceptions\ProfileException;
use Heart\User\Application\FindProfile;
use Heart\User\Domain\Actions\GetProfile;
use Heart\User\Domain\Entities\ProfileEntity;
use Heart\User\Domain\Entities\UserEntity;
use Heart\User\Domain\Repositories\UserRepository;
use Mockery\MockInterface;
use Mockery as m;
use Tests\TestCase;
use Tests\Unit\Character\ProviderProviderTrait;
use Tests\Unit\User\ProfileProviderTrait;
use Tests\Unit\User\UserProviderTrait;

class FindProfileTest extends TestCase
{
    use UserProviderTrait;

    use ProviderProviderTrait;

    use ProfileProviderTrait;

    private MockInterface $userRepositoryStub;

    private MockInterface $getProfileStub;

    private MockInterface $providerRepositoryStub;

    private UserEntity $userEntity;

    private ProviderEntity $providerEntity;

    private ProfileEntity $profileEntity;

    public function setUp(): void
    {
        parent::setUp();
        $this->userRepositoryStub = m::mock(UserRepository::class);
        $this->getProfileStub = m::mock(GetProfile::class);
        $this->providerRepositoryStub = m::mock(ProviderRepository::class);
        $this->providerEntity = $this->validProviderEntity();
        $this->userEntity = $this->validUserEntity();
        $this->profileEntity = $this->validProfileEntity();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        m::close();
    }

    public function testFindProfileWithUsernameSuccess(): void
    {
        $this->userRepositoryStub
            ->shouldReceive('findByUsername')
            ->with('canhassi')
            ->once()
            ->andReturn($this->userEntity);

        $this->getProfileStub
            ->shouldReceive('handle')
            ->with($this->userEntity->id)
            ->once()
            ->andReturn($this->profileEntity);

        $test = new FindProfile($this->getProfileStub, $this->userRepositoryStub, $this->providerRepositoryStub);

        $test->handle("canhassi");
    }

    public function testFindProfileWithProviderIdSuccess(): void
    {
        $this->userRepositoryStub
            ->shouldReceive('findByUsername')
            ->with('canhassi-id')
            ->once();

        $this->providerRepositoryStub
            ->shouldReceive('findByProviderId')
            ->with('canhassi-id')
            ->once()
            ->andReturn($this->providerEntity);

        $this->getProfileStub
            ->shouldReceive('handle')
            ->with($this->providerEntity->userId)
            ->once()
            ->andReturn($this->profileEntity);

        $test = new FindProfile($this->getProfileStub, $this->userRepositoryStub, $this->providerRepositoryStub);

        $test->handle("canhassi-id");
    }

    public function testProfileNotFound(): void
    {
        $this->expectException(ProfileException::class);

        $this->userRepositoryStub
            ->shouldReceive('findByUsername')
            ->with('canhassi-id')
            ->once();

        $this->providerRepositoryStub
            ->shouldReceive('findByProviderId')
            ->with('canhassi-id')
            ->once();

        $test = new FindProfile($this->getProfileStub, $this->userRepositoryStub, $this->providerRepositoryStub);

        $test->handle("canhassi-id");
    }
}
