<?php

namespace App\Actions\Event\Meeting;

use App\Models\Events\Meeting;
use App\Repositories\Events\MeetingRepository;
use Illuminate\Support\Carbon;

class EndMeeting
{
    private MeetingRepository $meetingRepository;

    public function __construct(MeetingRepository $meetingRepository)
    {
        $this->meetingRepository = $meetingRepository;
    }

    public function handle(): string
    {
        $payload['ends_at'] = Carbon::now();

        $this->meetingRepository->updateActiveMeetings($payload);

        return __('meetings.success.endMeeting');
    }
}
