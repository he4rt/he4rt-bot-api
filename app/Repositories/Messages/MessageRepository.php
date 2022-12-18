<?php

namespace App\Repositories\Messages;

use App\Actions\Gamefication\GiveXP;
use App\Models\User\Message;
use App\Repositories\Users\UsersRepository;

class MessageRepository
{
    private UsersRepository $userRepository;
    private GiveXP $giveXP;

    public function __construct(UsersRepository $usersRepository, GiveXP $giveXP)
    {
        $this->userRepository = $usersRepository;
        $this->giveXP = $giveXP;
    }

    public function create(
        string $discordId,
        string $message
    ): Message {
        $user = $this->userRepository->findById($discordId);
        $obtainedExperience = $this->giveXP->handle(
            $user->getKey(),
            config('gambling.xp.message')
        );
        return Message::create([
            'season_id' => config('he4rt.season.id'),
            'user_id' => $user->getKey(),
            'message' => $message,
            'obtained_experience' => $obtainedExperience
        ]);
    }
}
