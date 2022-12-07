<?php

namespace App\Actions\Event\Meeting;

use App\Models\Events\Meeting;
use App\Repositories\Events\MeetingRepository;
use Illuminate\Support\Carbon;

class UpdateMeeting
{
    private MeetingRepository $meetingRepository;

    public function __construct(MeetingRepository $meetingRepository)
    {
        $this->meetingRepository = $meetingRepository;
    }

    public function handle(int $meetingId, array $payload): Meeting
    {
        $payload['ends_at'] = Carbon::now();

        return $this->meetingRepository->update($meetingId, $payload);
    }
}
