<?php

namespace Heart\Meeting\Domain\DTO;

class NewMeetingDTO
{
    public function __construct(public readonly int $meetingTypeId, public readonly  int $discordId)
    {
    }
}
