<?php

namespace Heart\Message\Domain\Repositories;

use Heart\Message\Infrastructure\Models\Message;

interface MessageRepository
{
    public function create(array $payload): Message;
}
