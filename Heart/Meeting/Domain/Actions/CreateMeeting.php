<?php

namespace Heart\Meeting\Domain\Actions;

use Heart\Meeting\Domain\DTO\NewMeetingDTO;
use Heart\Meeting\Domain\Entities\MeetingEntity;
use Heart\Meeting\Domain\Repositories\MeetingRepository;

class CreateMeeting
{
    public function __construct(private readonly MeetingRepository $repository)
    {
    }

    public function handle(NewMeetingDTO $dto, string $adminId): MeetingEntity
    {
        return $this->repository->create($dto, $adminId);
    }
}
