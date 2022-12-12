<?php

namespace App\Actions\Gamefication;

use App\Exceptions\UserException;
use App\Repositories\Users\UsersRepository;

class ClaimVoiceXP
{
    private UsersRepository $userRepository;
    private GiveXP $giveXp;

    public function __construct(
        UsersRepository $userRepository,
        GiveXP $giveXP
    ) {
        $this->userRepository = $userRepository;
        $this->giveXp = $giveXP;
    }

    /** @throws UserException */
    public function handle(int $discordId)
    {
        if (!$user = $this->userRepository->findById($discordId)) {
            throw UserException::discordIdNotFound($discordId);
        }

        $points = config('gambling.xp.voice_points');

        $this->giveXp->handle($user->getKey(), $points);
    }
}
