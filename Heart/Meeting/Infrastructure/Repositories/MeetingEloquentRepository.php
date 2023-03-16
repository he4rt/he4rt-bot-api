<?php

namespace Heart\Meeting\Infrastructure\Repositories;

use Heart\Meeting\Domain\DTO\NewMeetingDTO;
use Heart\Meeting\Domain\Entities\MeetingEntity;
use Heart\Meeting\Domain\Repositories\MeetingRepository;
use Heart\Meeting\Infrastructure\Models\Meeting;
use Heart\Shared\Domain\Paginator;
use Heart\Shared\Infrastructure\Paginator as PaginatorConcrete;

class MeetingEloquentRepository implements MeetingRepository
{
    public function __construct(private readonly Meeting $model)
    {
    }

    public function paginate(array $relations = [], int $perPage = 10): Paginator
    {
        $meetings = $this->model->newQuery()->with($relations)->paginate($perPage);

        return PaginatorConcrete::paginate($meetings);
    }

    public function create(NewMeetingDTO $dto, string $adminId): MeetingEntity
    {
        $meeting = $this->model->newQuery()->create([
            'meeting_type_id' => $dto->meetingTypeId,
            'admin_id' => $adminId,
            'starts_at' => now(),
        ]);

        return MeetingEntity::make($meeting->toArray());
    }

    public function endMeeting(string $meetingId): MeetingEntity
    {
        $this->model
            ->newQuery()
            ->find($meetingId)
            ->update(['ends_at' => now()]);

        $meeting = $this->model
            ->newQuery()
            ->find($meetingId);

        return MeetingEntity::make($meeting->toArray());
    }

    public function attendMeeting(string $meetingId, string $userId): void
    {
        $this->model->newQuery()
            ->find($meetingId)
            ->users()
            ->attach($userId, ['attend_at' => now()]);
    }
}
