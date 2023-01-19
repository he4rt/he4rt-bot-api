<?php

namespace Heart\Provider\Domain\Actions;

use Heart\Provider\Domain\Entities\ProviderEntity;
use Heart\Provider\Domain\Repositories\ProviderRepository;
use Heart\Provider\Infrastructure\Models\Provider;

class GetProviderById
{
    public function __construct(private readonly ProviderRepository $providerRepository)
    {
    }

    public function handle(string $provider, string $providerId): ProviderEntity
    {
        return $this->providerRepository->findByProvider($provider, $providerId);
    }
}
