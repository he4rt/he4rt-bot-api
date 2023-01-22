<?php

namespace Heart\Badges\Domain\Entities;

class BadgeEntity
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $description,
        public readonly string $redeemCode,
        public readonly bool $active
    ) {
    }

    public static function make(array $payload): self
    {
        return new self(
            id: $payload['id'],
            name: $payload['name'],
            description: $payload['description'],
            redeemCode: $payload['redeem_code'],
            active: $payload['active']
        );
    }
}
