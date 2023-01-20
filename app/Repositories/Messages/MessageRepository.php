<?php

namespace App\Repositories\Messages;

use App\Actions\Gamefication\GiveXP;
use App\Models\User\Message;
use App\Repositories\Users\UsersRepository;
use Carbon\Carbon;

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
        array  $messagePayload
    ): Message
    {
        $user = $this->userRepository->findById($discordId);

        $obtainedExperience = $this->obtainExperience($user->getKey(), $messagePayload['channel_id']);
        $messagePayload['sent_at'] = Carbon::createFromTimestamp($messagePayload['sent_at']);
        $persist = [
                'season_id' => config('he4rt.season.id'),
                'user_id' => $user->getKey(),
                'obtained_experience' => $obtainedExperience,
            ] + $messagePayload;
        return Message::create($persist);
    }

    public function obtainExperience(string $userId, string $channel_id): int
    {
        $obtainedExperience = 0;
        if (!in_array($channel_id, config('he4rt.channels'))) {
            $obtainedExperience = $this->giveXP->handle($userId, config('gambling.xp.message'));
        }

        return $obtainedExperience;
    }
}
