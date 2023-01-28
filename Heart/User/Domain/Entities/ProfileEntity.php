<?php

namespace Heart\User\Domain\Entities;

use Heart\Badges\Domain\Collections\BadgeCollection;
use Heart\Character\Domain\Collections\PastSeasonCollection;
use Heart\Character\Domain\Entities\CharacterEntity;
use JsonSerializable;

class ProfileEntity implements JsonSerializable
{
    public function __construct(
        public readonly string $id,
        public readonly string $username,
        public readonly InformationEntity $informationEntity,
        public readonly AddressEntity $addressEntity,
        public readonly CharacterEntity $characterEntity,
        public readonly array $connectedProviders,
        public readonly BadgeCollection $badges,
        public readonly PastSeasonCollection $pastSeasons,
    ) {
    }

    public static function make(array $payload): self
    {

        $badges = BadgeCollection::make($payload['character']['badges']);
        $pastSeasons = PastSeasonCollection::make($payload['character']['past_seasons']);

        return new self(
            id: $payload['id'],
            username: $payload['username'],
            informationEntity: InformationEntity::make($payload['information']),
            addressEntity: AddressEntity::make($payload['address']),
            characterEntity: CharacterEntity::make($payload['character']),
            connectedProviders: $payload['providers'],
            badges: $badges,
            pastSeasons: $pastSeasons
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
            'connectedProviders' => $this->connectedProviders,
            'badges' => $this->badges,
            'pastSeasons' => $this->pastSeasons
        ];
    }
}
