<?php

namespace Heart\Badges\Domain\DTOs;

class NewBadgeDTO implements \JsonSerializable
{
    public function __construct(
        private readonly string $provider,
        private readonly string $name,
        private readonly string $description,
        private readonly string $imageUrl,
        private readonly string $redeemCode,
        private readonly bool $active,
    ) {
    }

    public static function make(array $payload): self
    {
        return new self(
            provider: $payload['provider'],
            name: $payload['name'],
            description: $payload['description'],
            imageUrl: $payload['image_url'],
            redeemCode: $payload['redeem_code'],
            active: $payload['active']
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'provider' => $this->provider,
            'name' => $this->name,
            'description' => $this->description,
            'image_url' => $this->imageUrl,
            'redeem_code' => $this->redeemCode,
            'active' => $this->active
        ];
    }
}
