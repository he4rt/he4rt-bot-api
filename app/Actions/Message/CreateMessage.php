<?php

namespace App\Actions\Message;

use App\Repositories\Messages\MessageRepository;

class CreateMessage
{
    private MessageRepository $repository;

    public function __construct(MessageRepository $repository)
    {
        $this->repository = $repository;
    }

    public function handle(string $discordId, string $message): void
    {
        $this->repository->create($discordId, $message);
    }
}
