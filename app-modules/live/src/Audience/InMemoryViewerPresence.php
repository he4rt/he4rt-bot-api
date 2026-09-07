<?php

declare(strict_types=1);

namespace He4rt\Live\Audience;

use He4rt\Live\Contracts\ViewerPresenceContract;

/** Presença em memória para testes e ambientes sem Redis. */
final class InMemoryViewerPresence implements ViewerPresenceContract
{
    private const int WINDOW_SECONDS = 30;

    /** @var array<string, array<string, int>> */
    private array $heartbeats = [];

    public function touch(string $liveId, string $visitorId): void
    {
        $this->heartbeats[$liveId][$visitorId] = now()->getTimestamp();
    }

    public function countActive(string $liveId): int
    {
        $cutoff = now()->getTimestamp() - self::WINDOW_SECONDS;

        $this->heartbeats[$liveId] = array_filter(
            $this->heartbeats[$liveId] ?? [],
            static fn (int $timestamp): bool => $timestamp > $cutoff,
        );

        return count($this->heartbeats[$liveId]);
    }
}
