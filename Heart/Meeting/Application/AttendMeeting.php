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
        $meetingId = Cache::get('current-meeting');

        $this->persistAttendMeeting->handle($meetingId, $userId);
        $ttl = 60 * 60 * 2;
        $userAttendedCacheKey = sprintf('meeting-%s-attended', $userId);
        Cache::set($userAttendedCacheKey, true, $ttl);
    }
}
