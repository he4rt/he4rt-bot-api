<?php

namespace Heart\Meeting\Application;

use Heart\Meeting\Domain\Actions\CreateMeeting as CreateMeetingAction;
use Heart\Meeting\Domain\DTO\NewMeetingDTO;
use Heart\Shared\Domain\Paginator;

class CreateMeeting
{
    public function __construct(private readonly CreateMeetingAction $createMeetingAction)
    {
    }

    public function handle(int $meetingTypeId, int $discordId): Paginator
    {
        $meetingDto = new NewMeetingDTO($meetingTypeId, $discordId);

        return $this->createMeetingAction->handle($meetingDto);
    }
}
