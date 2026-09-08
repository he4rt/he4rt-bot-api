<?php

declare(strict_types=1);

namespace He4rt\Marketing\ShortLink\Support;

use Illuminate\Support\Str;

/**
 * Builds `{nickname}-{5 base36 characters}` slugs.
 *
 * The nickname makes the link readable. The random suffix makes it unique
 * without a retry loop and hides how many links exist.
 *
 * The suffix uses `random_int` over an explicit lowercase alphabet.
 * `Str::random()` draws from `[A-Za-z0-9]` and would produce uppercase
 * characters that the `[a-z0-9-]+` route constraint rejects.
 */
final class SlugGenerator
{
    public const int SUFFIX_LENGTH = 5;

    public const string SUFFIX_ALPHABET = '0123456789abcdefghijklmnopqrstuvwxyz';

    public static function for(string $nickname): string
    {
        return self::base($nickname).'-'.self::suffix();
    }

    /**
     * The nickname half of the slug. It has its own indexed column, so all the
     * links that share a nickname stay one cheap query.
     */
    public static function base(string $nickname): string
    {
        return Str::slug($nickname);
    }

    public static function suffix(): string
    {
        $alphabet = self::SUFFIX_ALPHABET;
        $lastIndex = mb_strlen($alphabet) - 1;
        $suffix = '';

        for ($i = 0; $i < self::SUFFIX_LENGTH; $i++) {
            $suffix .= $alphabet[random_int(0, $lastIndex)];
        }

        return $suffix;
    }
}
