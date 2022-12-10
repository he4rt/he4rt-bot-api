<?php

namespace App\Actions\Event\Meeting;

use App\Models\Events\Meeting;
use App\Repositories\Events\MeetingRepository;

class AddMeetingSubject
{
    private MeetingRepository $meetingRepository;

    public function __construct(MeetingRepository $meetingRepository)
    {
        $this->meetingRepository = $meetingRepository;
    }

    public function handle(int $meetingId, array $payload): Meeting
    {
        return $this->meetingRepository->update($meetingId, $payload);
    }
}
