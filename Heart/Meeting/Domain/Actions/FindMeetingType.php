<?php

namespace Heart\Meeting\Domain\Actions;

use Heart\Meeting\Domain\Entities\MeetingTypeEntity;
use Heart\Meeting\Domain\Exceptions\MeetingException;
use Heart\Meeting\Domain\Repositories\MeetingTypeRepository;

class FindMeetingType
{
    public function __construct(private readonly MeetingTypeRepository $meetingTypeRepository)
    {
    }

    /**
     * @throws MeetingException
     */
    public function handle(int $meetingType): MeetingTypeEntity
    {
        $meetingTypeEntity = $this->meetingTypeRepository->findById($meetingType);

        if (!$meetingTypeEntity) {
            throw MeetingException::meetingTypeNotFound();
        }

        return $meetingTypeEntity;
    }
}
