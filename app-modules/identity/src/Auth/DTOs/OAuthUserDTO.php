<?php

declare(strict_types=1);

namespace He4rt\Identity\Auth\DTOs;

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;

abstract class OAuthUserDTO
{
    public function __construct(
        public OAuthAccessDTO $credentials,
        public string $providerId,
        public IdentityProvider $provider,
        public string $username,
        public string $name,
        public ?string $email,
        public ?string $avatarUrl,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    abstract public static function make(OAuthAccessDTO $credentials, array $payload): self;

    /** @return array<string, mixed> */
    public function toMetadata(): array
    {
        return [
            ...($this->email !== null ? ['email' => $this->email] : []),
            ...($this->avatarUrl !== null ? ['avatar' => $this->avatarUrl] : []),
            'username' => $this->username,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    final public function toDatabase(): array
    {
        return [
            'provider' => $this->provider,
            'external_account_id' => $this->providerId,
            'email' => $this->email,
        ];
    }
}
