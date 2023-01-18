<?php

namespace Heart\Message\Domain\Repositories;

use Heart\Message\Domain\DTO\NewMessageDTO;
use Heart\Message\Infrastructure\Models\Message;

interface MessageRepository
{
    public function create(NewMessageDTO $payload, int $obtainedExperience): Message;
}
