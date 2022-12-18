<?php

namespace App\Actions\Gamefication;

use App\Exceptions\UserException;
use App\Models\User\User;
use App\Repositories\Users\UsersRepository;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class GiveXP
{
    private UsersRepository $repository;

    public function __construct(UsersRepository $repository)
    {
        $this->repository = $repository;
    }

    /** @throws UserException */
    public function handle(int $userId, int $points): int
    {

        $user = $this->getUser($userId);

        $points = $this->getXP($user, $points);

        if (!$this->canLevelUp($user, $points)) {
            $this->repository->incrementExperience($user->getKey(), $points);
            return $points;
        }

        $this->levelUp($user);

        return $points;
    }

    /** @throws UserException */
    private function getUser(int $userId): User
    {
        if (!$user = $this->repository->find($userId)) {
            throw UserException::discordIdNotFound($userId);
        }

        return $user;
    }

    private function getXP(User $user, int $points): int
    {
        return $points * ($user->is_donator ? 2 : 1);
    }

    private function canLevelUp(User $user, int $points): bool
    {
        return ($user->current_exp + $points) >= $user->nextLevel->required;
    }

    private function levelUp(User $user): void
    {
        $currentXp = $user->current_exp - $user->nextLevel->required;
        $this->repository->levelUp(
            $user->getKey(),
            $currentXp,
        );
    }
}
