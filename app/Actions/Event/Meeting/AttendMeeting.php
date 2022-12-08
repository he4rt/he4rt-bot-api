<?php

namespace App\Actions\Event\Meeting;

use App\Exceptions\MeetingsException;
use App\Repositories\Events\MeetingRepository;
use App\Repositories\Users\UsersRepository;

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
    public function handle(array $payload): void
    {
        $user =  $this->usersRepository->findById($payload['discord_id']);
        $meeting = $this->meetingRepository->getActiveMeeting();

        if (!$meeting) {
            throw MeetingsException::noActiveMeetings();
        }

        $alreadyAttended = $meeting->users()->find($user->getKey());
        if ($alreadyAttended) {
            throw MeetingsException::alreadyAttended();
        }

        $this->usersRepository->attendMeeting($payload['discord_id'], $meeting->getKey());
    }
}
