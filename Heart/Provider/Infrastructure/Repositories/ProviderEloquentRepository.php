<?php

namespace Heart\Provider\Infrastructure\Repositories;

use Heart\Authentication\OAuth\Domain\DTO\OAuthUserDTO;
use Heart\Provider\Domain\DTOs\NewProviderDTO;
use Heart\Provider\Domain\Entities\ProviderEntity;
use Heart\Provider\Domain\Repositories\ProviderRepository;
use Heart\Provider\Infrastructure\Exceptions\ProviderException;
use Heart\Provider\Infrastructure\Models\Provider;

class ProviderEloquentRepository implements ProviderRepository
{
    public function findByProvider(string $provider, string $providerId): ProviderEntity
    {
        $model = Provider::query()
            ->where('provider', $provider)
            ->where('provider_id', $providerId)
            ->first();

        if (!$model) {
            throw ProviderException::notFound($provider, $providerId);
        }

        return ProviderEntity::make($model->toArray());
    }

    public function create(string $userId, NewProviderDTO $providerDTO): ProviderEntity
    {
        $model = Provider::create([
            'user_id' => $userId,
            ...$providerDTO->jsonSerialize(),
        ]);

        return ProviderEntity::make($model->toArray());
    }

    public function findByProviderId(string $providerId): ?ProviderEntity
    {
        $model = Provider::where('provider_id', $providerId)->first();

        return ProviderEntity::make($model->toArray());
    }

    public function getProvider(string $provider, string $providerId): ?ProviderEntity
    {
        $model = Provider::query()
            ->where('provider', $provider)
            ->where('provider_id', $providerId)
            ->first();

        if (!$model) {
            return null;
        }

        return ProviderEntity::make($model->toArray());
    }
}
