<?php

namespace Tests\Unit\Meeting\Domain\Actions;

use Heart\Meeting\Domain\Actions\FinishMeeting;
use Heart\Meeting\Domain\Entities\MeetingEntity;
use Heart\Meeting\Domain\Repositories\MeetingRepository;
use Mockery as m;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Unit\Meeting\MeetingProviderTrait;

class FinishMeetingTest extends TestCase
{
    use MeetingProviderTrait;
    private MockInterface $meetingRepositoryStub;

    private MeetingEntity $meetingEntity;
    public function setUp(): void
    {
        parent::setUp();
        $this->meetingRepositoryStub = m::mock(MeetingRepository::class);
        $this->meetingEntity = $this->validMeetingEntity();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        m::close();
    }

    public function testFinishMeeting(): void
    {
        $this->meetingRepositoryStub
            ->shouldReceive('endMeeting')
            ->with($this->meetingEntity->id)
            ->once()
            ->andReturn($this->meetingEntity);

        $test = new FinishMeeting($this->meetingRepositoryStub);

        $test->handle($this->meetingEntity->id);
    }
}
