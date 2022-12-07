<?php

namespace App\Repositories\Events;

use App\Models\Events\Meeting;

class MeetingRepository
{
    public function create(array $payload): Meeting
    {
        return Meeting::create($payload);
    }

    public function find(int $meetingId): Meeting
    {
        return Meeting::find($meetingId);
    }

    public function update(int $meetingId, array $payload): Meeting
    {
        $model = $this->find($meetingId);
        $model->update($payload);

        return $model->refresh();
    }
}
