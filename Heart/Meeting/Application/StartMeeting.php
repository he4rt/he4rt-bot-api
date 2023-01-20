<?php

namespace Heart\Meeting\Application;

use Heart\Meeting\Domain\Actions\CreateMeeting as CreateMeetingAction;
use Heart\Meeting\Domain\DTO\NewMeetingDTO;
use Heart\Meeting\Domain\Entities\MeetingEntity;
use Heart\Provider\Application\FindProvider;
use Heart\Shared\Application\TTL;
use Heart\Shared\Domain\Paginator;
use Illuminate\Support\Facades\Cache;

class StartMeeting
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
        $currentMeeting = $this->createMeetingAction->handle($meetingDTO, $providerEntity->userId);
        $this->setMeetingCache($currentMeeting);

        return $currentMeeting;
    }

    public function setMeetingCache(MeetingEntity $currentMeeting): void
    {
        Cache::tags(['meetings'])->put('current-meeting', $currentMeeting->id, TTL::fromHours(2));
    }
}
