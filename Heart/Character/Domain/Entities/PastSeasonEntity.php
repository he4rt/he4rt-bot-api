<?php

namespace Heart\Character\Domain\Entities;

use JsonSerializable;

class PastSeasonEntity implements JsonSerializable
{
    public function __construct(
        private readonly string $id,
        private readonly string $seasonId,
        private readonly string $characterId,
        private readonly int $rankingPosition,
        private readonly int $experience,
        private readonly int $messagesCount,
        private readonly int $badgesCount,
        private readonly int $meetingsCount,
    ) {
    }

    public static function make(array $payload): self
    {
        return new self(
            id: $payload['id'],
            seasonId: $payload['season_id'],
            characterId: $payload['character_id'],
            rankingPosition: $payload['ranking_position'],
            experience: $payload['experience'],
            messagesCount: $payload['messages_count'],
            badgesCount: $payload['badges_count'],
            meetingsCount: $payload['meetings_count'],
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'season_id' => $this->seasonId,
            'character_id' => $this->characterId,
            'ranking_position' => $this->rankingPosition,
            'experience' => $this->experience,
            'messages_count' => $this->messagesCount,
            'badges_count' => $this->badgesCount,
            'meetings_count' => $this->meetingsCount,
        ];
    }
}
