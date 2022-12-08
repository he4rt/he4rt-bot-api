<?php

namespace App\Actions\Event\Meeting;

use App\Exceptions\MeetingsException;
use App\Models\Events\MeetingParticipants;
use App\Repositories\Events\MeetingParticipantsRepository;
use App\Repositories\Events\MeetingRepository;
use App\Repositories\Users\UsersRepository;
use Illuminate\Support\Carbon;

class AttendMeeting
{
    private MeetingParticipantsRepository $repository;
    private UsersRepository $usersRepository;
    private MeetingRepository $meetingRepository;

    public function __construct(
        MeetingParticipantsRepository $repository,
        UsersRepository               $usersRepository,
        MeetingRepository             $meetingRepository
    )
    {
        $this->repository = $repository;
        $this->usersRepository = $usersRepository;
        $this->meetingRepository = $meetingRepository;
    }

    public function handle(array $payload): MeetingParticipants
    {
        $meeting = $this->meetingRepository->find($payload['meeting_id']);
        $userParticipant = $this->usersRepository->findById($payload['discord_id']);

        if ($meeting->isEnded()) {
            throw MeetingsException::meetingEnded();
        }

        $payload['attend_at'] = Carbon::now();
        $payload['user_id'] = $userParticipant->getKey();

        return $this->repository->create($payload);
    }
}
