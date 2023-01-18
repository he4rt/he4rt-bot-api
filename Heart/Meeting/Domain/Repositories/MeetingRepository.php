<?php

namespace Heart\Meeting\Domain\Repositories;

use Heart\Shared\Domain\Paginator;

interface MeetingRepository
{
    public function paginate(array $relations = [], int $perPage = 10): Paginator;
}
