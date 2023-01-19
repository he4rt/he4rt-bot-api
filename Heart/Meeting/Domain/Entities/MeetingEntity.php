<?php

namespace Heart\Meeting\Domain\Entities;

use DateTime;

class MeetingEntity
{
    public function __construct(
        public int $id,
        public string $content,
        public int $meetingTypeId,
        public int $adminId,
        public DateTime $startsAt,
        public DateTime $endsAt,
        public DateTime $createdAt,
        public DateTime $updatedAt,
    ) {
    }
}
