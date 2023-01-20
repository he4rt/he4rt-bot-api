<?php

namespace Heart\Meeting\Domain\Actions;

use Heart\Meeting\Domain\Repositories\MeetingRepository;

class PersistAttendMeeting
{
    public function __construct(private readonly MeetingRepository $meetingRepository)
    {
    }

    public function handle(string $meetingId, string $userId)
    {
        $this->meetingRepository->attendMeeting($meetingId, $userId);
    }
}
