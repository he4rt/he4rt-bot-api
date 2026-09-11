<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\OAuth;

use He4rt\Identity\Auth\DTOs\OAuthAccessDTO;
use He4rt\Identity\Auth\DTOs\OAuthUserDTO;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;

class DiscordOAuthUser extends OAuthUserDTO
{
    public function __construct(
        OAuthAccessDTO $credentials,
        string $providerId,
        IdentityProvider $provider,
        string $username,
        string $name,
        ?string $email,
        ?string $avatarUrl,
        private readonly bool $avatarProvided = false,
    ) {
        parent::__construct($credentials, $providerId, $provider, $username, $name, $email, $avatarUrl);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function make(OAuthAccessDTO $credentials, array $payload): OAuthUserDTO
    {
        return new self(
            credentials: $credentials,
            providerId: $payload['id'],
            provider: IdentityProvider::Discord,
            username: $payload['username'],
            name: $payload['global_name'] ?? $payload['username'],
            email: $payload['email'] ?? null,
            avatarUrl: isset($payload['avatar'])
                ? sprintf('https://cdn.discordapp.com/avatars/%s/%s.png', $payload['id'], $payload['avatar'])
                : null,
            avatarProvided: array_key_exists('avatar', $payload),
        );
    }

    /** @return array<string, mixed> */
    public function toMetadata(): array
    {
        return [
            ...($this->avatarProvided && $this->avatarUrl === null ? ['avatar' => null] : []),
            ...parent::toMetadata(),
            'global_name' => $this->name,
        ];
    }
}
