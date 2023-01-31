<?php

namespace Heart\Season\Domain\Entities;

use DateTime;

class SeasonEntity
{
    public function __construct(
        public string $id,
        public string $name,
        public string $description,
        public int $messagesCount,
        public int $participantsCount,
        public int $meetingCount,
        public int $badgesCount,
        public DateTime $startAt,
        public DateTime $endAt,
    ) {
    }

    public static function make(array $payload): self
    {
        $endsAt = ! empty($payload['ended_at'])
            ? new DateTime($payload['ended_at'])
            : null;

        return new self(
            id: $payload['id'],
            name: $payload['name'],
            description: $payload['description'],
            messagesCount: $payload['messages_count'],
            participantsCount: $payload['participants_count'],
            meetingCount: $payload['meeting_count'],
            badgesCount: $payload['badges_count'],
            startAt: new DateTime($payload['started_at']),
            endAt: $endsAt,
        );
    }
}
