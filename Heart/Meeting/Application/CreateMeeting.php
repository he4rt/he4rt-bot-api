<?php

namespace Heart\Meeting\Application;

use Heart\Meeting\Domain\Actions\CreateMeeting as CreateMeetingAction;
use Heart\Meeting\Domain\DTO\NewMeetingDTO;
use Heart\Meeting\Domain\Entities\MeetingEntity;
use Heart\Provider\Application\FindProvider;
use Heart\Shared\Domain\Paginator;

class CreateMeeting
{
    public function __construct(
        private readonly CreateMeetingAction $createMeetingAction,
        private readonly FindProvider $findProvider,
    ) {
    }

    public function handle(string $provider, string $providerId, int $meetingTypeId): MeetingEntity
    {
        $meetingDTO = NewMeetingDTO::make($provider, $providerId, $meetingTypeId);
        $providerEntity = $this->findProvider->handle($provider, $providerId);
        return $this->createMeetingAction->handle($meetingDTO, $providerEntity->userId);
    }
}
