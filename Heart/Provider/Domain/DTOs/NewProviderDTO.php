<?php

namespace Heart\Provider\Domain\DTOs;

use Heart\Provider\Domain\Enums\ProviderEnum;
use JsonSerializable;

class NewProviderDTO implements JsonSerializable

{
    public function __construct(
        private readonly ProviderEnum $provider,
        private readonly string $providerId
    ) {
    }


    public function jsonSerialize(): array
    {
        return [
            'provider' => $this->provider->value,
            'provider_id' => $this->providerId,
        ];
    }
}
