<?php

namespace Tests\Unit\Meeting\Domain\Actions;

use Heart\Meeting\Domain\Actions\PersistAttendMeeting;
use Heart\Meeting\Domain\DTO\NewMeetingDTO;
use Heart\Meeting\Domain\Entities\MeetingEntity;
use Heart\Meeting\Domain\Repositories\MeetingRepository;
use Mockery\MockInterface;
use Mockery as m;
use Tests\TestCase;
use Tests\Unit\Meeting\MeetingProviderTrait;

class PersistAttendMeetingTest extends TestCase
{
    use MeetingProviderTrait;
    private MockInterface $meetingTypeRepositoryStub;

    private MeetingEntity $meetingEntity;

    private NewMeetingDTO $payloadDTO;

    public function setUp(): void
    {
        parent::setUp();
        $this->meetingTypeRepositoryStub = m::mock(MeetingRepository::class);
        $this->meetingEntity = $this->validMeetingEntity();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        m::close();
    }

    public function testPersistAttendMeeting(): void
    {
        $this->meetingTypeRepositoryStub
            ->shouldReceive('attendMeeting')
            ->with($this->meetingEntity->id, 12)
            ->once();

        $test = new PersistAttendMeeting($this->meetingTypeRepositoryStub);

        $test->handle($this->meetingEntity->id, 12);
    }
}
