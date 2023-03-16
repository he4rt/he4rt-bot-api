<?php

namespace Heart\User\Domain\Entities;

class AddressEntity implements \JsonSerializable
{
    public function __construct(
        public string $id,
        private ?string $country,
        private ?string $state,
        private ?string $city,
        private ?int $zipCode,
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
