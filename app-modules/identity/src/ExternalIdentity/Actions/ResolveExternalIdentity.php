<?php

declare(strict_types=1);

namespace He4rt\Identity\ExternalIdentity\Actions;

use He4rt\Identity\ExternalIdentity\Data\ClientAccessManager;
use He4rt\Identity\ExternalIdentity\DTOs\ResolveUserProviderDTO;
use He4rt\Identity\ExternalIdentity\Enums\CredentialsType;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;

final class ResolveExternalIdentity
{
    public function handle(ResolveUserProviderDTO $dto): ExternalIdentity
    {
        return ExternalIdentity::query()->firstOrCreate(
            [
                'provider' => $dto->provider,
                'external_account_id' => $dto->externalAccountId,
                'model_type' => $dto->modelType,
            ],
            [
                'type' => $dto->provider->getType(),
                'credentials_type' => CredentialsType::OAuth2,
                'credentials' => ClientAccessManager::make(),
                'metadata' => array_filter([
                    'username' => $dto->username,
                    'email' => $dto->email,
                    'avatar' => $dto->avatar,
                ]),
            ]
        );
    }
}
