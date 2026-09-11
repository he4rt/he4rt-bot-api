<?php

declare(strict_types=1);

namespace He4rt\Identity\Auth\Actions;

use He4rt\Identity\ExternalIdentity\Data\ClientAccessManager;
use He4rt\Identity\ExternalIdentity\Enums\CredentialsType;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Events\ExternalIdentityConnected;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;

final class PersistOAuthConnection
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function execute(
        User $owner,
        IdentityProvider $provider,
        string $providerId,
        ClientAccessManager $credentials,
        array $metadata,
        ?string $connectedBy,
    ): ExternalIdentity {
        /** @var ExternalIdentity $identity */
        $identity = $owner->providers()->firstOrNew([
            'provider' => $provider,
            'external_account_id' => $providerId,
        ]);

        $identity->forceFill([
            'type' => $provider->getType(),
            'credentials_type' => CredentialsType::OAuth2,
            'credentials' => $credentials,
            'metadata' => array_replace($identity->metadata ?? [], $metadata),
            'connected_at' => now(),
            'disconnected_at' => null,
            'connected_by' => $connectedBy,
        ])->save();

        event(new ExternalIdentityConnected($identity));

        return $identity;
    }
}
