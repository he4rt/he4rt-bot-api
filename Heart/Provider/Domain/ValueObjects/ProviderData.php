<?php

namespace Heart\Provider\Domain\ValueObjects;

class ProviderData
{
    public function __construct(
        private readonly string $provider,
        private readonly string $providerId,
        private readonly string $providerEmail
    )
    {
    }


    public function getProviderId(): string
    {
        return $this->providerId;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }
}
