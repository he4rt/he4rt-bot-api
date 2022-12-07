<?php

namespace App\Repositories\Events;

use App\Models\Events\Meeting;

class MeetingRepository
{
    public function create(array $payload): Meeting
    {
        return Meeting::create($payload);
    }
}
