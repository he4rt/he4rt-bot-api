<?php

declare(strict_types=1);

use He4rt\Contents\Data\TagList;

test('fromArray normalizes strings, trims, dedupes and reindexes', function (): void {
    $tags = TagList::fromArray([' php ', 'laravel', 'php', '', '   ', 123, null, 'laravel']);

    expect($tags->toArray())->toBe(['php', 'laravel']);
});

test('empty payload produces an empty list', function (): void {
    $tags = TagList::fromArray([]);

    expect($tags->isEmpty())->toBeTrue()
        ->and($tags->toArray())->toBeEmpty();
});

test('contains checks membership', function (): void {
    $tags = TagList::fromArray(['php', 'laravel']);

    expect($tags->contains('php'))->toBeTrue()
        ->and($tags->contains('ruby'))->toBeFalse();
});

test('default constructor produces an empty list', function (): void {
    $tags = new TagList();

    expect($tags->isEmpty())->toBeTrue();
});
