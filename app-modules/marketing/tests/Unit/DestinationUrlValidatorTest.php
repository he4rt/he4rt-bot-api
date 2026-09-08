<?php

declare(strict_types=1);

use He4rt\Marketing\ShortLink\Exceptions\InvalidDestinationUrl;
use He4rt\Marketing\ShortLink\Support\DestinationUrlValidator;

test('http and https destinations are accepted', function (string $url): void {
    expect(DestinationUrlValidator::assert($url))->toBe($url);
})->with([
    'https://discord.gg/he4rt',
    'http://example.com',
    'https://example.com/path?a=1#anchor',
]);

test('non-http schemes are rejected', function (string $url): void {
    expect(fn () => DestinationUrlValidator::assert($url))
        ->toThrow(InvalidDestinationUrl::class);
})->with([
    'javascript:alert(1)',
    'data:text/html;base64,PHNjcmlwdD4=',
    'file:///etc/passwd',
    '/relative/path',
    '',
]);
