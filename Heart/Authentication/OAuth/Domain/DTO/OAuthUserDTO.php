<?php

namespace Heart\Authentication\OAuth\Domain\DTO;

abstract class OAuthUserDTO
{
    public function __construct(
        public OAuthAccessDTO $credentials,
        public string $providerId,
        public string $providerName,
        public string $username,
        public string $name,
        public ?string $email,
        public ?string $avatarUrl,
    ) {
    }

    abstract public static function make(OAuthAccessDTO $credentials, array $payload): self;

    public function toDatabase(): array
    {
        return [
            'provider' => $this->providerName,
            'provider_id' => $this->providerId,
            'email' => $this->email,
        ];
    }
}
