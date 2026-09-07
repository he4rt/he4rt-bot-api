<?php

declare(strict_types=1);

namespace He4rt\Live\Audience;

use He4rt\Live\Contracts\ViewerPresenceContract;
use Illuminate\Support\Facades\Redis;

/** Presença em sorted set no Redis: score = timestamp do último heartbeat. */
final readonly class RedisViewerPresence implements ViewerPresenceContract
{
    private const int WINDOW_SECONDS = 30;

    public function touch(string $liveId, string $visitorId): void
    {
        Redis::connection()->zadd($this->key($liveId), now()->getTimestamp(), $visitorId);
    }

    public function countActive(string $liveId): int
    {
        $connection = Redis::connection();
        $cutoff = now()->getTimestamp() - self::WINDOW_SECONDS;

        $connection->zremrangebyscore($this->key($liveId), '-inf', (string) $cutoff);

        return (int) $connection->zcard($this->key($liveId));
    }

    private function key(string $liveId): string
    {
        return 'live:viewers:'.$liveId;
    }
}
