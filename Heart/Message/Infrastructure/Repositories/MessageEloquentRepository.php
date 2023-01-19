<?php

namespace Heart\Message\Infrastructure\Repositories;

use Heart\Message\Domain\DTO\NewMessageDTO;
use Heart\Message\Domain\Entities\MessageEntity;
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

    public function create(NewMessageDTO $messageDTO, string $providerId, int $obtainedExperience): MessageEntity
    {
        $model = $this->query->create([
            'provider_id' => $providerId,
            'provider_message_id' => $messageDTO->providerMessageId,
            'season_id' => (int)config('he4rt.season.id'),
            'channel_id' => $messageDTO->channelId,
            'content' => $messageDTO->content,
            'sent_at' => $messageDTO->sentAt,
            'obtained_experience' => $obtainedExperience,
        ]);
        return MessageEntity::make($model->toArray());
    }
}
