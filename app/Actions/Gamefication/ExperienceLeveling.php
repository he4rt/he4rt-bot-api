<?php

namespace App\Actions\Gamefication;

use App\Repositories\Users\UsersRepository;
use Illuminate\Support\Facades\Artisan;

class ExperienceLeveling
{
    private UsersRepository $userRepository;

    public function __construct(UsersRepository $repository)
    {
        $this->userRepository = $repository;
    }

    public function handle(int $userId): int
    {
        $user = $this->userRepository->find($userId);

        $givenExp = $this->generateExp($user->is_donator);
        $actualExperience = $user->current_exp;

        $experienceNeededToLevelUp = $user->nextLevel->required;
        $userExperience = $actualExperience + $givenExp;
        if ($userExperience < $experienceNeededToLevelUp) {
            $this->userRepository->incrementExperience($userId, $givenExp);
            return $givenExp;
        }

        $currentExp = $userExperience - $experienceNeededToLevelUp;
        $this->userRepository->levelUp($userId, $currentExp);
        $user = $user->refresh();

        Artisan::call('discord:level-up', [
            'discordId' => $user->discord_id,
            'level' => $user->level,
            'messagesCount' => $user->seasonMessagesCount()
        ]);

        return $givenExp;
    }

    private function generateExp(bool $isDonator)
    {
        return rand(1, 5) * ($isDonator ? 2 : 1);
    }

}
