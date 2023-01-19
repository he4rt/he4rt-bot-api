<?php

namespace Heart\Message\Domain\Repositories;

use Heart\Message\Domain\DTO\NewMessageDTO;
use Heart\Message\Domain\Entities\MessageEntity;

interface MessageRepository
{
    public function create(NewMessageDTO $payload, string $providerId, int $obtainedExperience): MessageEntity;
}
