<?php

namespace Heart\Provider\Domain\Repositories;

use Heart\Authentication\OAuth\Domain\DTO\OAuthUserDTO;
use Heart\Provider\Domain\DTOs\NewProviderDTO;
use Heart\Provider\Domain\Entities\ProviderEntity;
use Heart\Provider\Domain\Enums\ProviderEnum;

interface ProviderRepository
{
    public function findByProvider(string $provider, string $providerId): ProviderEntity;

    public function findByProviderId(string $providerId): ?ProviderEntity;

    public function getProvider(string $provider, string $providerId): ?ProviderEntity;

    public function create(string $userId, NewProviderDTO $providerDTO): ProviderEntity;
}
