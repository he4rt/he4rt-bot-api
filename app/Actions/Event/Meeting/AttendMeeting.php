<?php

namespace App\Actions\Event\Meeting;

use App\Exceptions\MeetingsException;
use App\Repositories\Events\MeetingRepository;
use App\Repositories\Users\UsersRepository;

class AttendMeeting
{
    private UsersRepository $usersRepository;
    private MeetingRepository $meetingRepository;
    private GetActiveMeeting $activeMeetingAction;

    public function __construct(
        UsersRepository   $usersRepository,
        MeetingRepository $meetingRepository,
        GetActiveMeeting  $activeMeetingAction
    )
    {
        $this->usersRepository = $usersRepository;
        $this->meetingRepository = $meetingRepository;
        $this->activeMeetingAction = $activeMeetingAction;
    }

    /**
     * @throws MeetingsException
     */
    public function handle(array $payload): void
    {
        $meeting = $this->activeMeetingAction->handle();
        $user = $this->usersRepository->findById($payload['discord_id']);

        $alreadyAttended = $meeting->users()->find($user->getKey());
        if ($alreadyAttended) {
            throw MeetingsException::alreadyAttended();
        }

        $this->usersRepository->attendMeeting($payload['discord_id'], $meeting->getKey());
    }
}
