<?php

namespace Heart\Meeting\Domain\Repositories;

use Heart\Meeting\Domain\DTO\NewMeetingDTO;
use Heart\Meeting\Domain\Entities\MeetingEntity;
use Heart\Shared\Domain\Paginator;

interface MeetingRepository
{
    public function paginate(array $relations = [], int $perPage = 10): Paginator;

    public function create(NewMeetingDTO $dto, string $adminId): MeetingEntity;

    public function endMeeting(string $meetingId): MeetingEntity;
}
