<?php

namespace Heart\Message\Domain\Actions;

use Heart\Message\Domain\DTO\NewMessageDTO;
use Heart\Message\Domain\Entities\MessageEntity;
use Heart\Message\Domain\Repositories\MessageRepository;

class PersistMessage
{
    public function __construct(
        private readonly MessageRepository $messageRepository
    ) {
    }

    public function handle(
        NewMessageDTO $messageDTO,
        int $obtainedExperience,
        string $providerEntity
    ): MessageEntity {
        return $this->messageRepository->create($messageDTO, $providerEntity, $obtainedExperience);
    }
}
