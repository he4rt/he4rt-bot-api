<?php

declare(strict_types=1);

namespace He4rt\Marketing\ShortLink\Observers;

use He4rt\Marketing\ShortLink\Models\ShortLink;
use He4rt\Marketing\ShortLink\Support\ShortLinkCache;

/**
 * Clears the redirect cache after every edit.
 *
 * Positive cache entries are written forever, so an edit is the only thing that
 * can make one stale. This catches every edit, whatever made it.
 */
final class ShortLinkObserver
{
    public function saved(ShortLink $link): void
    {
        $this->forget($link);
    }

    public function deleted(ShortLink $link): void
    {
        $this->forget($link);
    }

    public function restored(ShortLink $link): void
    {
        $this->forget($link);
    }

    private function forget(ShortLink $link): void
    {
        // A rewritten slug leaves its stale entry under the old key.
        $original = $link->getOriginal('slug');

        if (is_string($original) && $original !== '' && $original !== $link->slug) {
            ShortLinkCache::forget($original);
        }

        ShortLinkCache::forget($link->slug);
    }
}
