<?php

namespace Heart\Integrations\Twitch\Subscriber\Domain\DTO;

use Heart\Integrations\Twitch\Subscriber\Domain\Enum\SubscriptionTiersEnum;

class TwitchSubscriberDTO
{
    public function __construct(
        public readonly string                $broadcasterId,
        public readonly string                $broadcasterLogin,
        public readonly string                $broadcasterName,
        public readonly SubscriptionTiersEnum $tier,
        public readonly bool                  $isGift,
        public readonly ?string               $gifterLogin = null,
        public readonly ?string               $gifterName = null,
    )
    {
    }

    public static function make(array $payload): self
    {
        return new self(
            $payload['broadcaster_id'],
            $payload['broadcaster_login'],
            $payload['broadcaster_name'],
            SubscriptionTiersEnum::from($payload['tier']),
            (bool)$payload['is_gift'],
            $payload['gifter_login'] ?? null,
            $payload['gifter_name'] ?? null,
        );
    }
}
