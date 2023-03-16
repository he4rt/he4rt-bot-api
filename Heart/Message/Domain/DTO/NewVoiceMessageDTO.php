<?php

namespace Heart\Message\Domain\DTO;

use Heart\Character\Domain\Enums\VoiceStatesEnum;
use Heart\Provider\Domain\Enums\ProviderEnum;

class NewVoiceMessageDTO
{
    public function __construct(
        public readonly ProviderEnum $provider,
        public readonly string $providerId,
        public readonly VoiceStatesEnum $voiceState,
        public readonly string $channelName,
    ) {
    }

    public static function make(array $payload): self
    {
        return new self(
            provider: ProviderEnum::from($payload['provider']),
            providerId: $payload['provider_id'],
            voiceState: VoiceStatesEnum::from($payload['state']),
            channelName: $payload['channel_name']
        );
    }
}
