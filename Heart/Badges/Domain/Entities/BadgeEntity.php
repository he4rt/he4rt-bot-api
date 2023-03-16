<?php

namespace Heart\Badges\Domain\Entities;

use JsonSerializable;

class BadgeEntity implements JsonSerializable
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $description,
        public readonly string $redeemCode,
        public readonly bool $active,
        public readonly string $imageUrl,
    ) {
    }

    public static function make(array $payload): self
    {
        return new self(
            id: $payload['id'],
            name: $payload['name'],
            description: $payload['description'],
            redeemCode: $payload['redeem_code'],
            active: $payload['active'],
            imageUrl: $payload['image_url'],
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'image_url' => $this->imageUrl,
        ];
    }
}
