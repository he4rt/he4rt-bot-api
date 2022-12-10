<?php

namespace App\Actions\Event\Meeting;

use App\Repositories\Events\MeetingRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexMeeting
{
    private MeetingRepository $meetingRepository;

    public function __construct(MeetingRepository $meetingRepository)
    {
        $this->meetingRepository = $meetingRepository;
    }

    public function handle(): LengthAwarePaginator
    {
        return $this->meetingRepository->getAll();
    }
}
