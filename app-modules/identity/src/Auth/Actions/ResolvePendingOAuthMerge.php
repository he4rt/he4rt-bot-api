<?php

declare(strict_types=1);

namespace He4rt\Identity\Auth\Actions;

use He4rt\Identity\Auth\DTOs\PendingOAuthMergeDTO;
use He4rt\Identity\ExternalIdentity\Data\ClientAccessManager;
use He4rt\Identity\ExternalIdentity\Enums\CredentialsType;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;

final class ResolvePendingOAuthMerge
{
    public function execute(mixed $payload): ?PendingOAuthMergeDTO
    {
        if (!is_array($payload)) {
            return null;
        }

        $conflictingUserId = $payload['conflicting_user_id'] ?? null;
        $providerValue = $payload['provider'] ?? null;
        $providerId = $payload['provider_id'] ?? null;
        $credentials = $payload['credentials'] ?? null;
        $metadata = $payload['metadata'] ?? null;

        if (
            !is_string($conflictingUserId)
            || !is_string($providerValue)
            || !is_string($providerId)
            || !is_array($credentials)
            || !is_array($metadata)
        ) {
            return null;
        }

        $accessToken = $credentials['access_token'] ?? null;
        $refreshToken = $credentials['refresh_token'] ?? null;
        $expiresIn = $credentials['expires_in'] ?? null;
        $provider = IdentityProvider::tryFrom($providerValue);

        if (
            !is_string($accessToken)
            || !is_string($refreshToken)
            || (!is_string($expiresIn) && $expiresIn !== null)
            || !$provider instanceof IdentityProvider
            || $provider->getCredentialsType() !== CredentialsType::OAuth2
        ) {
            return null;
        }

        /** @var array<string, mixed> $metadata */
        return new PendingOAuthMergeDTO(
            conflictingUserId: $conflictingUserId,
            provider: $provider,
            providerId: $providerId,
            credentials: ClientAccessManager::make(
                accessToken: $accessToken,
                refreshToken: $refreshToken,
                expiresIn: $expiresIn,
            ),
            metadata: $metadata,
        );
    }
}
