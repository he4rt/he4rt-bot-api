<?php

namespace App\Actions\Event\Meeting;

use App\Models\Events\Meeting;
use App\Repositories\Events\MeetingRepository;
use App\Repositories\Users\UsersRepository;
use Illuminate\Support\Carbon;

class CreateMeeting
{
    private MeetingRepository $meetingRepository;
    private UsersRepository $usersRepository;

    public function __construct(MeetingRepository $meetingRepository, UsersRepository $usersRepository)
    {
        $this->meetingRepository = $meetingRepository;
        $this->usersRepository = $usersRepository;
    }

    public function handle(array $payload): Meeting
    {
        $userCreator = $this->usersRepository->findById($payload['discord_id']);

        $payload['starts_at'] = Carbon::now();
        $payload['admin_id'] = $userCreator->getKey();

        return $this->meetingRepository->create($payload);
    }
}
