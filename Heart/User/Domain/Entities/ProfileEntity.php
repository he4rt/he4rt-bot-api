<?php

namespace Heart\User\Domain\Entities;

use Heart\Badges\Domain\Collections\BadgeCollection;
use Heart\Badges\Domain\Entities\BadgeEntity;
use Heart\Character\Domain\Entities\CharacterEntity;

class ProfileEntity implements \JsonSerializable
{
    public function __construct(
        public readonly string $id,
        public readonly string $username,
        public readonly array $informationEntity,
        public readonly array $addressEntity,
        public readonly CharacterEntity $characterEntity,
        public readonly array $connectedProviders,
        public readonly BadgeCollection $badges
    ) {
    }

    public static function make(array $payload): self
    {
        $badges = new BadgeCollection();

        foreach ($payload['character']['badges'] as $badge) {
            $badges->add(BadgeEntity::make($badge));
        }

        return new self(
            id: $payload['id'],
            username: $payload['username'],
            informationEntity: [],
            addressEntity: [],
            characterEntity: CharacterEntity::make($payload['character']),
            connectedProviders: $payload['providers'],
            badges: $badges
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'information' => $this->informationEntity,
            'address' => $this->addressEntity,
            'character' => $this->characterEntity,
            'providers' => $this->connectedProviders,
            'badges' => $this->badges
        ];
    }
}
