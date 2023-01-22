<?php

namespace Heart\Meeting\Domain\Actions;

use Heart\Meeting\Domain\Entities\MeetingEntity;
use Heart\Meeting\Domain\Repositories\MeetingRepository;

class FinishMeeting
{
    public function __construct(private readonly MeetingRepository $meetingRepository)
    {
    }

    public function handle(string $meetingId): MeetingEntity
    {
        return $this->meetingRepository->endMeeting($meetingId);
    }
}
