<?php

namespace Heart\Message\Domain\DTO;

use DateTime;

class ProviderMessageDTO
{
    public function __construct(
        public string   $providerId,
        public string   $providerMessageId,
        public string   $channelId,
        public string   $content,
        public DateTime $sentAt,
    )
    {
    }

    public static function make(array $payload): self
    {
        return new self(
            providerId: $payload['provider_id'],
            providerMessageId: $payload['provider_message_id'],
            channelId: $payload['channel_id'],
            content: $payload['content'],
            sentAt: $payload['sent_at']
        );
    }

    public function toArray()
    {

    }
}
