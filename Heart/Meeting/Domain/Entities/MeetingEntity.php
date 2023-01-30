<?php

namespace Heart\Meeting\Domain\Entities;

use DateTime;

class MeetingEntity
{
    public function __construct(
        public string $id,
        public ?string $content,
        public int $meetingTypeId,
        public string $adminId,
        public DateTime $startsAt,
        public ?DateTime $endsAt,
        public DateTime $createdAt,
        public DateTime $updatedAt,
    ) {
    }

    public static function make(array $payload): self
    {
        $endsAt = ! empty($payload['ends_at'])
            ? new DateTime($payload['ends_at'])
            : null;

        return new self(
            id: $payload['id'],
            content: $payload['content'] ?? null,
            meetingTypeId: $payload['meeting_type_id'],
            adminId: $payload['admin_id'],
            startsAt: new DateTime($payload['starts_at']),
            endsAt: $endsAt,
            createdAt: new DateTime($payload['created_at']),
            updatedAt: new DateTime($payload['updated_at'])
        );
    }
}
