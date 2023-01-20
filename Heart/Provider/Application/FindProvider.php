<?php

namespace Heart\Provider\Application;

use Heart\Provider\Domain\Actions\GetProviderById;
use Heart\Provider\Domain\Entities\ProviderEntity;
use Illuminate\Support\Facades\Cache;

class FindProvider
{
    public final const TTL = 60 * 60 * 24;

    public function __construct(private readonly GetProviderById $action)
    {
    }

    public function handle(string $provider, string $providerId): ProviderEntity
    {
        $providerCacheKey = sprintf('provider-%s-%s', $provider, $providerId);

        return Cache::remember(
            $providerCacheKey,
            self::TTL,
            fn() => $this->findProvider($provider, $providerId)
        );
    }

    private function findProvider(string $provider, string $providerId): ProviderEntity
    {
        return $this->action->handle($provider, $providerId);
    }
}
