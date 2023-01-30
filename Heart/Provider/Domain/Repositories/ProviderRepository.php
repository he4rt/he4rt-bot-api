<?php

namespace Heart\Provider\Domain\Repositories;

use Heart\Authentication\OAuth\Domain\DTO\OAuthUserDTO;
use Heart\Provider\Domain\Entities\ProviderEntity;

interface ProviderRepository
{
    public function findByProvider(string $provider, string $providerId): ProviderEntity;

    public function findByProviderId(string $providerId): ?ProviderEntity;

    public function create(string $subscriberId, OAuthUserDTO $dto): ProviderEntity;
}
