<?php

namespace Heart\Provider\Domain\Repositories;

use Heart\Authentication\OAuth\Domain\DTO\OAuthUserDTO;
use Heart\Provider\Domain\Entities\ProviderEntity;
use Heart\Provider\Infrastructure\Models\Provider;

interface ProviderRepository
{
    public function findByProvider(string $provider, string $providerId): ProviderEntity;

    public function findByProviderId(string $providerId): ?Provider;

    public function create(string $subscriberId, OAuthUserDTO $dto): Provider;
}
