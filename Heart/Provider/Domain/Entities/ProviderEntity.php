<?php

namespace Heart\Provider\Domain\Entities;

use Heart\Provider\Domain\ValueObjects\ProviderData;

class ProviderEntity
{
    public readonly string $id;

    public readonly string $userId;

    public readonly ProviderData $provider;

    public function __construct(
        string $id,
        string $userId,
        string $provider,
        string $providerId,
        ?string $providerEmail
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->provider = new ProviderData($provider, $providerId, $providerEmail);
    }

    public static function make(array $payload): self
    {
        return new self(
            $payload['id'],
            $payload['user_id'],
            $payload['provider'],
            $payload['provider_id'],
            $payload['email'],
        );
    }
}
