<?php

namespace App\Actions\Event\Meeting;

use App\Models\Events\Meeting;
use App\Repositories\Events\MeetingRepository;
use App\Repositories\Users\UsersRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

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
