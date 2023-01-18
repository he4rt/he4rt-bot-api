<?php

namespace Heart\Message\Domain\Actions;

use Heart\Message\Domain\Repositories\MessageRepository;

class PersistMessage
{
    public function __construct(
        private readonly MessageRepository $messageRepository
    )
    {
    }

    public function handle()
    {
        $this->messageRepository->create();
    }
}
