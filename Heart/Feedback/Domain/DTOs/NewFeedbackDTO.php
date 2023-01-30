<?php

namespace Heart\Feedback\Domain\DTOs;

use JsonSerializable;

class NewFeedbackDTO implements JsonSerializable
{
    public function __construct(
        private readonly string $senderId,
        private readonly string $targetId,
        private readonly string $type,
        private readonly string $message
    ) {
    }

    public static function make(array $payload): self
    {
        return new self(
            $payload['sender_id'],
            $payload['target_id'],
            $payload['type'],
            $payload['message']
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'sender_id' => $this->senderId,
            'target_id' => $this->targetId,
            'type' => $this->type,
            'message' => $this->message
        ];
    }
}
