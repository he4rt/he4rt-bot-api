<?php

namespace Heart\Provider\Application;

use Heart\Provider\Domain\Actions\GetProviderById;
use Heart\Provider\Domain\Entities\ProviderEntity;
use Heart\Shared\Application\TTL;
use Illuminate\Support\Facades\Cache;

class FindProvider
{
    public function __construct(private readonly GetProviderById $action)
    {
    }

    public function handle(string $provider, string $providerId): ProviderEntity
    {
        $providerCacheKey = sprintf('provider-%s-%s', $provider, $providerId);

        return Cache::remember(
            $providerCacheKey,
            TTL::fromDays(2),
            fn() => $this->findProvider($provider, $providerId)
        );
    }

    private function findProvider(string $provider, string $providerId): ProviderEntity
    {
        return $this->action->handle($provider, $providerId);
    }
}
