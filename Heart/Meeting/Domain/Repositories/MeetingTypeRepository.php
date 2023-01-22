<?php

namespace Heart\Meeting\Domain\Repositories;

use Heart\Meeting\Domain\Entities\MeetingTypeEntity;

interface MeetingTypeRepository
{
    public function findById(int $meetingTypeId): ?MeetingTypeEntity;
}
