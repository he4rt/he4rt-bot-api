<?php

declare(strict_types=1);

namespace He4rt\Marketing\ShortLink\Exceptions;

use InvalidArgumentException;

/**
 * Raised when a destination URL cannot be a redirect target.
 *
 * A short link is a public hop under our own domain, so the browser runs the
 * destination with our domain as the referrer. Only `http` and `https` are
 * allowed. A `javascript:`, `data:` or `file:` destination would make the
 * shortener an XSS or local-file vector.
 */
final class InvalidDestinationUrl extends InvalidArgumentException
{
    public static function unsupportedScheme(string $url, ?string $scheme): self
    {
        return new self(sprintf(
            'Destination "%s" uses the unsupported scheme "%s". Only http and https are allowed.',
            $url,
            $scheme ?? '(none)',
        ));
    }

    public static function malformed(string $url): self
    {
        return new self(sprintf('Destination "%s" is not a parsable absolute URL.', $url));
    }
}
