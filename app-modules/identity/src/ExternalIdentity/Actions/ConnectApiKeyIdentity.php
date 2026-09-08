<?php

declare(strict_types=1);

namespace He4rt\Identity\ExternalIdentity\Actions;

use App\Contracts\ApiKeyClientContract;
use He4rt\Identity\ExternalIdentity\Data\ClientAccessManager;
use He4rt\Identity\ExternalIdentity\Enums\CredentialsType;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Events\ExternalIdentityConnected;
use He4rt\Identity\ExternalIdentity\Exceptions\InvalidApiKeyException;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use Illuminate\Support\Facades\Crypt;
use InvalidArgumentException;

final class ConnectApiKeyIdentity
{
    /**
     * @throws InvalidApiKeyException quando o provider rejeita a chave
     * @throws InvalidArgumentException quando o provider não autentica por API key
     */
    public function execute(User $owner, IdentityProvider $provider, string $apiKey): ExternalIdentity
    {
        $providerAcceptsApiKey = $provider->getCredentialsType() === CredentialsType::ApiKey;

        if (!$providerAcceptsApiKey) {
            throw new InvalidArgumentException($provider->getLabel().' does not authenticate via API key.');
        }

        $client = $provider->getApiKeyClient();

        if (!$client instanceof ApiKeyClientContract) {
            throw new InvalidArgumentException('No API key client registered for '.$provider->getLabel().'.');
        }

        $profile = $client->getAuthenticatedUser($apiKey);

        /** @var ExternalIdentity $identity */
        $identity = $owner->providers()->updateOrCreate(
            [
                'provider' => $provider,
                'external_account_id' => $profile->providerId,
            ],
            [
                'type' => $provider->getType(),
                'credentials_type' => CredentialsType::ApiKey,
                'credentials' => ClientAccessManager::make(apiKey: Crypt::encrypt($apiKey)),
                'metadata' => $profile->toMetadata(),
                'connected_at' => now(),
                'disconnected_at' => null,
                'connected_by' => auth()->id(),
            ]
        );

        event(new ExternalIdentityConnected($identity));

        return $identity;
    }
}
