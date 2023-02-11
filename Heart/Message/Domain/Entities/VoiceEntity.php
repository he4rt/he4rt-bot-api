<?php

namespace Heart\Message\Domain\Entities;

use Heart\Character\Domain\Enums\VoiceStatesEnum;

class VoiceEntity
{
    public function __construct(
        public string $id,
        public string $providerId,
        public int $seasonId,
        public string $channelName,
        public VoiceStatesEnum $voiceState,
        public int $obtainedExperience,
    ) {
    }

    public static function make(array $payload): self
    {
        return new self(
            id: $payload['id'],
            providerId: $payload['provider_id'],
            seasonId: $payload['season_id'],
            channelName: $payload['channel_name'],
            voiceState: VoiceStatesEnum::from($payload['state']),
            obtainedExperience: $payload['obtained_experience']
        );
    }
}
