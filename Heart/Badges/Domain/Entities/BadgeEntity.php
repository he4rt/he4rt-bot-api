<?php

namespace Heart\Badges\Domain\Entities;

class BadgeEntity
{
    public function __construct(
        private readonly int $id,
        private readonly string $name,
        private readonly string $description,
        private readonly string $redeemCode,
        private readonly bool $active
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
