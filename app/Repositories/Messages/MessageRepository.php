<?php

namespace App\Repositories\Messages;

use App\Models\User\Message;
use App\Repositories\Users\UsersRepository;

class MessageRepository
{
    private UsersRepository $userRepository;

    public function __construct(UsersRepository $usersRepository)
    {
        $this->userRepository = $usersRepository;
    }

    public function create(
        string $discordId,
        string $message
    ): Message
    {
        $user = $this->userRepository->findById($discordId);

        return Message::create([
            'user_id' => $user->getKey(),
            'message' => $message,
        ]);
    }
}
