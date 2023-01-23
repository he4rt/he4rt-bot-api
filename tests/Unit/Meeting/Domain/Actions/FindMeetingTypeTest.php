<?php

namespace Tests\Unit\Meeting\Domain\Actions;

use Heart\Meeting\Domain\Actions\FindMeetingType;
use Heart\Meeting\Domain\Entities\MeetingTypeEntity;
use Heart\Meeting\Domain\Exceptions\MeetingException;
use Heart\Meeting\Domain\Repositories\MeetingTypeRepository;
use Mockery as m;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Unit\Meeting\MeetingTypeProviderTrait;

class FindMeetingTypeTest extends TestCase
{
    use MeetingTypeProviderTrait;
    private MockInterface $meetingTypeRepositoryStub;

    private MeetingTypeEntity $meetingTypeEntity;

    public function setUp(): void
    {
        parent::setUp();
        $this->meetingTypeRepositoryStub = m::mock(MeetingTypeRepository::class);
        $this->meetingEntity = $this->validMeetingTypeEntity();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        m::close();
    }

    public function testMeetingTypeIsNotFound(): void
    {
        $this->expectException(MeetingException::class);

        $this->meetingTypeRepositoryStub
            ->shouldReceive('findById')
            ->with(12)
            ->once()
            ->andReturn(null);

        $test = new FindMeetingType($this->meetingTypeRepositoryStub);

        $test->handle(12);
    }

    /**
     * @throws MeetingException
     */
    public function testFindMeetingTypeSuccess(): void
    {
        $this->meetingTypeRepositoryStub
            ->shouldReceive('findById')
            ->with(2)
            ->once()
            ->andReturn($this->meetingEntity);

        $test = new FindMeetingType($this->meetingTypeRepositoryStub);

        $test->handle(2);
    }
}
