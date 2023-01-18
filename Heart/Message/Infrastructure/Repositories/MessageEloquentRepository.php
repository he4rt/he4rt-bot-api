<?php

namespace Heart\Message\Infrastructure\Repositories;

use Heart\Message\Domain\Repositories\MessageRepository;
use Heart\Message\Infrastructure\Models\Message;
use Illuminate\Database\Eloquent\Builder;

class MessageEloquentRepository implements MessageRepository
{
    private Builder $query;

    public function __construct(private readonly Message $model)
    {
        $this->query = $this->model->newQuery();
    }

    public function create(array $payload): Message
    {
        return $this->query->create($payload);
    }
}
