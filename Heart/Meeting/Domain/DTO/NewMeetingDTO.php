<?php

namespace Heart\Meeting\Domain\DTO;

use Heart\Provider\Domain\Enums\ProviderEnum;

class NewMeetingDTO
{
    public function __construct(
        public readonly ProviderEnum $provider,
        public readonly string $providerId,
        public readonly int $meetingTypeId,
    ) {
    }

    public static function make(string $provider, string $providerId, int $meetingTypeId): self
    {
        return new self(
            provider: ProviderEnum::from($provider),
            providerId: $providerId,
            meetingTypeId: $meetingTypeId
        );
    }
}
