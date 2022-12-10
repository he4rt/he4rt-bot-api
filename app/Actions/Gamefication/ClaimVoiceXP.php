<?php

namespace App\Actions\Gamefication;

use App\Exceptions\UserException;
use App\Models\User\User;
use App\Repositories\Gamification\GamblingRepository;
use App\Repositories\Users\UsersRepository;

class ClaimVoiceXP
{
    private UsersRepository $userRepository;

    public function __construct(
        UsersRepository $userRepository
    ) {
        $this->userRepository = $userRepository;
    }

    /** @throws UserException */
    public function handle(int $discordId)
    {
        if (!$user = $this->userRepository->findById($discordId)) {
            throw UserException::discordIdNotFound($discordId);
        }

        $user->update(['money', $this->getPoints($user)]);
    }

    private function getPoints(User $user): int
    {
        return $user->money + config('gambling.voice_points');
    }
}
