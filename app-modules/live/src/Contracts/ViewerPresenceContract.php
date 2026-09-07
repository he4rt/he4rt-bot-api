<?php

declare(strict_types=1);

namespace He4rt\Live\Contracts;

interface ViewerPresenceContract
{
    public function touch(string $liveId, string $visitorId): void;

    /** Espectadores com heartbeat nos últimos 30 segundos. */
    public function countActive(string $liveId): int;
}
