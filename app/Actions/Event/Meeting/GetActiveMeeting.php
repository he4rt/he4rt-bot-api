<?php

namespace App\Actions\Event\Meeting;

use App\Exceptions\MeetingsException;
use App\Models\Events\Meeting;
use App\Repositories\Events\MeetingRepository;
use Illuminate\Support\Facades\Cache;

class GetActiveMeeting
{
    private MeetingRepository $meetingRepository;

    public function __construct(MeetingRepository $meetingRepository)
    {
        $this->meetingRepository = $meetingRepository;
    }

    public function handle(): Meeting
    {
        return Cache::remember('meeting-status', 60 * 5, fn () => $this->retrieveActiveMeeting());
    }

    private function retrieveActiveMeeting(): Meeting
    {
        $meeting = $this->meetingRepository->getActiveMeeting();
        if (!$meeting) {
            throw MeetingsException::noActiveMeetings();
        }


        return $meeting;
    }
}
