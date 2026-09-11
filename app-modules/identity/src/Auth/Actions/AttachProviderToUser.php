<?php

declare(strict_types=1);

namespace He4rt\Identity\Auth\Actions;

use He4rt\Identity\Auth\DTOs\OAuthAccessDTO;
use He4rt\Identity\Auth\DTOs\OAuthUserDTO;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;

final readonly class AttachProviderToUser
{
    public function __construct(private PersistOAuthConnection $persistConnection) {}

    public function execute(User $owner, OAuthUserDTO $oauthUser, OAuthAccessDTO $access): ExternalIdentity
    {
        $authenticatedUserId = auth()->id();

        return $this->persistConnection->execute(
            owner: $owner,
            provider: $oauthUser->provider,
            providerId: $oauthUser->providerId,
            credentials: $access->toClientAccessManager(),
            metadata: $oauthUser->toMetadata(),
            connectedBy: is_string($authenticatedUserId) ? $authenticatedUserId : null,
        );
    }
}
