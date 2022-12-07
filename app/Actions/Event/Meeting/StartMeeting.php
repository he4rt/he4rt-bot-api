<?php

namespace App\Actions\Event\Meeting;

use App\Models\Events\Badge;
use App\Repositories\Events\BadgeRepository;
use App\Repositories\Users\UsersRepository;
use Illuminate\Support\Carbon;

class StartMeeting
{
    private BadgeRepository $repository;
    private UsersRepository $usersRepository;

    public function __construct(BadgeRepository $repository, UsersRepository $usersRepository)
    {
        $this->repository = $repository;
        $this->usersRepository = $usersRepository;
    }

    public function handle(array $payload): Badge
    {
        $userCreator = $this->usersRepository->findById($payload['discord_id']);

        $payload['starts_at'] = Carbon::now();
        $payload['user_created_id'] = $userCreator->getKey();

        // todo criar repository
        return $this->repository->create($payload);
    }
}
