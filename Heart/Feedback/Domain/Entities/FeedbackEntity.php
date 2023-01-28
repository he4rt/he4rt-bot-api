<?php

namespace Heart\Feedback\Domain\Entities;

use JsonSerializable;

class FeedbackEntity implements JsonSerializable
{
    public function __construct(
        private readonly string $id,
        private readonly string $senderId,
        private readonly string $targetId,
        private readonly string $type,
        private readonly string $message,
    ) {
    }

    public static function make(array $payload): self
    {
        return new self(
            id: $payload['id'],
            senderId: $payload['sender_id'],
            targetId: $payload['target_id'],
            type: $payload['type'],
            message: $payload['message'],
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'sender_id' => $this->senderId,
            'target_id' => $this->targetId,
            'type' => $this->type,
            'message' => $this->message,
        ];
    }
}
