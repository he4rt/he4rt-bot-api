<?php

namespace App\Repositories\Events;

use App\Models\Events\Meeting;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MeetingRepository
{
    public function getAll(): LengthAwarePaginator
    {
        return Meeting::with('meetingType')->paginate();
    }

    public function create(array $payload): Meeting
    {
        return Meeting::create($payload);
    }

    public function find(int $meetingId): Meeting
    {
        return Meeting::findOrFail($meetingId);
    }

    public function getActiveMeeting(): ?Meeting
    {
        return Meeting::whereNull('ends_at')->first();
    }

    public function update(int $meetingId, array $payload): Meeting
    {
        $model = $this->find($meetingId);
        $model->update($payload);

        return $model->refresh();
    }

    public function updateActiveMeetings(array $payload): void
    {
        Meeting::whereNull('ends_at')->update($payload);
    }
}
