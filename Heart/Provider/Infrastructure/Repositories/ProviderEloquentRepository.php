<?php

namespace Heart\Provider\Infrastructure\Repositories;

use Heart\Authentication\OAuth\Domain\DTO\OAuthUserDTO;
use Heart\Provider\Domain\Repositories\ProviderRepository;
use Heart\Provider\Infrastructure\Models\Provider;

class ProviderEloquentRepository implements ProviderRepository
{

    public function findByProvider(OAuthUserDTO $user): ?Provider
    {
        return Provider::where('provider', $user->providerName)
            ->where('provider_id', $user->providerId)
            ->first();
    }

    public function create(string $subscriberId, OAuthUserDTO $user): Provider
    {
        return Provider::create([
            'subscriber_id' => $subscriberId,
            ...$user->toDatabase()
        ]);
    }

    public function findByProviderId(string $providerId): ?Provider
    {
        return Provider::find($providerId);
    }
}
