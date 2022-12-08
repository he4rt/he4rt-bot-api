<?php

namespace App\Actions\Event\Meeting;

use App\Exceptions\MeetingsException;
use App\Repositories\Events\MeetingRepository;
use App\Repositories\Users\UsersRepository;
use Illuminate\Support\Carbon;

class AttendMeeting
{
    private UsersRepository $usersRepository;
    private MeetingRepository $meetingRepository;

    public function __construct(UsersRepository $usersRepository, MeetingRepository $meetingRepository)
    {
        $this->usersRepository = $usersRepository;
        $this->meetingRepository = $meetingRepository;
    }

    /**
     * @throws MeetingsException
     */
    public function handle(array $payload): string
    {
        $meeting = $this->meetingRepository->getActiveMeeting();
        if (!$meeting) {
            throw MeetingsException::noAtiveMeetings();
        }

        $this->usersRepository->attachMeeting($payload['discord_id'], $meeting->getKey(), ['attend_at' => Carbon::now()]);
        return __('meetings.success.attendMeeting');
    }
}
