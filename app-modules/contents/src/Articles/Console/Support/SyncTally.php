<?php

declare(strict_types=1);

namespace He4rt\Contents\Articles\Console\Support;

use He4rt\Contents\Enums\ContentProvider;
use He4rt\Contents\Models\ContentEntry;

final class SyncTally
{
    public int $discovered = 0;

    public int $created = 0;

    public int $updated = 0;

    public int $hydrated = 0;

    public int $failed = 0;

    /** @var list<SyncFailure> */
    public array $failures = [];

    private readonly float $startedAt;

    private ?float $finishedAt = null;

    public function __construct(public readonly ContentProvider $provider)
    {
        $this->startedAt = microtime(as_float: true);
    }

    public function record(ContentEntry $entry): void
    {
        if ($entry->wasRecentlyCreated) {
            $this->created++;

            return;
        }

        $this->updated++;
    }

    public function fail(SyncFailure $failure): void
    {
        $this->failed++;
        $this->failures[] = $failure;
    }

    public function finish(): void
    {
        $this->finishedAt = microtime(as_float: true);
    }

    public function elapsedSeconds(): float
    {
        return ($this->finishedAt ?? microtime(as_float: true)) - $this->startedAt;
    }

    /** @return array<int, string> */
    public function toRow(): array
    {
        return [
            $this->provider->getLabel(),
            (string) $this->discovered,
            $this->created > 0 ? "<fg=green>{$this->created}</>" : '0',
            (string) $this->updated,
            (string) $this->hydrated,
            $this->failed > 0 ? "<fg=red>{$this->failed}</>" : '0',
            number_format($this->elapsedSeconds(), 1).'s',
        ];
    }
}
