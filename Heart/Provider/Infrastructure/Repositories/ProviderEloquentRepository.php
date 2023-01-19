<?php

namespace Heart\Provider\Infrastructure\Repositories;

use Heart\Authentication\OAuth\Domain\DTO\OAuthUserDTO;
use Heart\Provider\Domain\Entities\ProviderEntity;
use Heart\Provider\Domain\Repositories\ProviderRepository;
use Heart\Provider\Infrastructure\Models\Provider;

class ProviderEloquentRepository implements ProviderRepository
{

    public function findByProvider(string $provider, string $providerId): ProviderEntity
    {
        dump($provider, $providerId);
        $model = Provider::query()
            ->where('provider', $provider)
            ->where('provider_id', $providerId)
            ->first();

        return ProviderEntity::make($model->toArray());
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
