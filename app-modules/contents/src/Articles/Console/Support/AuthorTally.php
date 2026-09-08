<?php

declare(strict_types=1);

namespace He4rt\Contents\Articles\Console\Support;

use He4rt\Contents\Models\ContentEntry;
use He4rt\Identity\User\Models\User;

final class AuthorTally
{
    public int $articles = 0;

    public int $created = 0;

    public int $updated = 0;

    private ?string $userLabel = null;

    public function __construct(public readonly string $handle) {}

    public function record(ContentEntry $entry): void
    {
        $this->articles++;

        if ($entry->wasRecentlyCreated) {
            $this->created++;
        } else {
            $this->updated++;
        }

        $this->userLabel ??= $this->resolveUserLabel($entry);
    }

    public function isLinked(): bool
    {
        return $this->userLabel !== null;
    }

    /** @return array<int, string> */
    public function toRow(): array
    {
        return [
            '@'.$this->handle,
            $this->userLabel ?? '<fg=yellow>sem vínculo</>',
            (string) $this->articles,
            $this->created > 0 ? "<fg=green>{$this->created}</>" : '0',
            (string) $this->updated,
        ];
    }

    private function resolveUserLabel(ContentEntry $entry): ?string
    {
        if ($entry->author_id === null) {
            return null;
        }

        $author = $entry->relationLoaded('author') ? $entry->author : User::query()->find($entry->author_id);

        return $author instanceof User ? "{$author->name} (@{$author->username})" : null;
    }
}
