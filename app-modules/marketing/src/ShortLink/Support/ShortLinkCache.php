<?php

declare(strict_types=1);

namespace He4rt\Marketing\ShortLink\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * The read-through cache in front of every redirect.
 *
 * It stores raw columns, never a status. `Resolution` evaluates the status on
 * each read, so an expiry needs no scheduled invalidation.
 *
 * Positive entries live forever, because only an edit can make one stale and
 * the observer clears the key on save. Negative entries live for a minute,
 * which keeps a slug scanner off Postgres without making a new slug wait.
 */
final class ShortLinkCache
{
    public const string KEY_PREFIX = 'shortlink:';

    public const int NEGATIVE_TTL_SECONDS = 60;

    /**
     * Marks a slug that does not resolve. An absent key also reads as `null`.
     */
    private const string MISSING = '__missing__';

    public static function key(string $slug): string
    {
        return self::KEY_PREFIX.$slug;
    }

    /**
     * @param  Closure(): (array<string, mixed>|null)  $resolve
     * @return array<string, mixed>|null
     */
    public static function remember(string $slug, Closure $resolve): ?array
    {
        $cached = Cache::get(self::key($slug));

        if ($cached === self::MISSING) {
            return null;
        }

        if (is_array($cached)) {
            return $cached;
        }

        $payload = $resolve();

        if ($payload === null) {
            Cache::put(self::key($slug), self::MISSING, self::NEGATIVE_TTL_SECONDS);

            return null;
        }

        Cache::forever(self::key($slug), $payload);

        return $payload;
    }

    public static function forget(string $slug): void
    {
        Cache::forget(self::key($slug));
    }

    public static function has(string $slug): bool
    {
        return Cache::get(self::key($slug)) !== null;
    }
}
