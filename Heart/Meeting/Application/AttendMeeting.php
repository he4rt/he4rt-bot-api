<?php

namespace Heart\Meeting\Application;

use Heart\Meeting\Domain\Actions\PersistAttendMeeting;
use Illuminate\Support\Facades\Cache;

class AttendMeeting
{
    public function __construct(
        private readonly PersistAttendMeeting $persistAttendMeeting
    ) {
    }

    public function handle(string $userId): void
    {
        $ttl = 60 * 60 * 2;
        $meetingId = Cache::tags(['meetings'])->get('current-meeting');

        $this->persistAttendMeeting->handle($meetingId, $userId);
        $userAttendedCacheKey = sprintf('meeting-%s-attended', $userId);
        Cache::tags(['meetings'])->put($userAttendedCacheKey, true, $ttl);
    }
}
