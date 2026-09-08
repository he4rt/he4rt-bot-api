<?php

declare(strict_types=1);

namespace He4rt\Contents\Articles\Actions;

use He4rt\Contents\Articles\Events\ArticlePublished;
use He4rt\Contents\Enums\ContentProvider;
use He4rt\Contents\Models\ContentEntry;
use He4rt\Identity\ExternalIdentity\Events\ExternalIdentityConnected;
use He4rt\Identity\User\Models\User;

final readonly class ReconcileOrphanEntries
{
    public function handle(ExternalIdentityConnected $event): void
    {
        $user = $event->identity->model;

        if (!$user instanceof User) {
            return;
        }

        $username = $event->identity->metadata['username'] ?? null;

        if (!is_string($username) || $username === '') {
            return;
        }

        $provider = ContentProvider::tryFromIdentityProvider($event->identity->provider);

        if (!$provider instanceof ContentProvider) {
            return;
        }

        $this->execute($user, $provider, $username);
    }

    public function execute(User $user, ContentProvider $provider, string $authorHandle): int
    {
        $entries = ContentEntry::query()
            ->where('provider', $provider)
            ->where('author_handle', $authorHandle)
            ->whereNull('author_id')
            ->get();

        foreach ($entries as $entry) {
            $entry->author_id = $user->id;
            $entry->save();

            event(new ArticlePublished($entry));
        }

        return $entries->count();
    }
}
