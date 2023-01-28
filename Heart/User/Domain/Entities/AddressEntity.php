<?php

namespace Heart\User\Domain\Entities;

class AddressEntity implements \JsonSerializable
{
    public function __construct(
        public readonly string $id,
        private readonly ?string $country,
        private readonly ?string $state,
        private readonly ?string $city,
        private readonly ?int $zipCode,
    ) {
    }

    public static function make(array $payload): self
    {
        return new self(
            id: $payload['id'],
            country: $payload['country'],
            state: $payload['state'],
            city: $payload['city'],
            zipCode: $payload['zip_code']
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'country' => $this->country,
            'state' => $this->state,
        ];
    }
}
