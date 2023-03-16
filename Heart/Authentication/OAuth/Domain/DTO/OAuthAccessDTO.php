<?php

namespace Heart\Authentication\OAuth\Domain\DTO;

abstract class OAuthAccessDTO
{
    public function __construct(
        public readonly string $accessToken,
        public readonly string $refreshToken,
        public readonly ?int $expiresIn
    ) {
    }

    abstract public static function make(array $payload): self;

    public function toDatabase(): array
    {
        return [
            'access_token' => $this->accessToken,
            'refresh_token' => $this->refreshToken,
            'expires_in' => $this->expiresIn,
        ];
    }
}
