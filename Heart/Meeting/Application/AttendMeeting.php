<?php

namespace Heart\Meeting\Application;

use Heart\Meeting\Domain\Actions\PersistAttendMeeting;
use Heart\Meeting\Domain\Exceptions\MeetingException;
use Heart\Shared\Application\TTL;
use Illuminate\Support\Facades\Cache;

class AttendMeeting
{
    public function __construct(
        private readonly PersistAttendMeeting $persistAttendMeeting
    ) {
    }

    public function handle(string $userId): void
    {

        $meetingId = $this->getMeetingId();

        $this->persistAttendMeeting->handle($meetingId, $userId);
        $userAttendedCacheKey = sprintf('meeting-%s-attended', $userId);
        Cache::tags(['meetings'])->put($userAttendedCacheKey, true, TTL::fromHours(2));
    }

    public function getMeetingId(): string
    {
        if (!Cache::tags(['meetings'])->has('current-meeting')) {
            throw MeetingException::nonexistentMeeting();
        }

        return Cache::tags(['meetings'])->get('current-meeting');
    }
}
