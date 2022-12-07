<?php

namespace App\Repositories\Events;

use App\Models\Events\MeetingParticipants;

class MeetingParticipantsRepository
{
    public function create(array $payload): MeetingParticipants
    {
        return MeetingParticipants::create($payload);
    }
}
