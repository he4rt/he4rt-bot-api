<?php

declare(strict_types=1);

use He4rt\Marketing\ShortLink\ValueObjects\TagList;

test('an empty list has no tags', function (): void {
    $tags = new TagList();

    expect($tags->toArray())->toBeEmpty()
        ->and($tags->isEmpty())->toBeTrue()
        ->and($tags)->toBeEmpty();
});

test('tags are lowercased', function (): void {
    expect(TagList::fromArray(['Comunidade', 'HACKTOBERFEST'])->toArray())
        ->toBe(['comunidade', 'hacktoberfest']);
});

test('tags are trimmed', function (): void {
    expect(TagList::fromArray(['  comunidade  ', "\thacktoberfest\n"])->toArray())
        ->toBe(['comunidade', 'hacktoberfest']);
});

test('duplicates are collapsed, including the ones that only differ by case or spacing', function (): void {
    expect(TagList::fromArray(['comunidade', 'Comunidade', ' COMUNIDADE '])->toArray())
        ->toBe(['comunidade']);
});

test('blank tags are dropped', function (): void {
    expect(TagList::fromArray(['comunidade', '', '   ', "\n"])->toArray())
        ->toBe(['comunidade']);
});

test('non string values are dropped', function (): void {
    expect(TagList::fromArray(['comunidade', null, ['nested'], true, new stdClass()])->toArray())
        ->toBe(['comunidade']);
});

test('numeric tags survive as strings', function (): void {
    expect(TagList::fromArray([12, 'evento'])->toArray())->toBe(['12', 'evento']);
});

test('tags are sorted alphabetically so two equal lists are identical', function (): void {
    expect(TagList::fromArray(['zulu', 'alpha', 'mike'])->toArray())
        ->toBe(['alpha', 'mike', 'zulu'])
        ->and(TagList::fromArray(['mike', 'zulu', 'alpha'])->toArray())
        ->toBe(TagList::fromArray(['alpha', 'mike', 'zulu'])->toArray());
});

test('contains normalizes the needle', function (): void {
    $tags = TagList::fromArray(['comunidade']);

    expect($tags->contains('comunidade'))->toBeTrue()
        ->and($tags->contains('  COMUNIDADE '))->toBeTrue()
        ->and($tags->contains('hacktoberfest'))->toBeFalse()
        ->and($tags->contains('   '))->toBeFalse();
});

test('add returns a new list and leaves the original untouched', function (): void {
    $original = TagList::fromArray(['comunidade']);
    $extended = $original->add('Hacktoberfest');

    expect($extended)->not->toBe($original)
        ->and($extended->toArray())->toBe(['comunidade', 'hacktoberfest'])
        ->and($original->toArray())->toBe(['comunidade']);
});

test('adding a tag that is already there changes nothing', function (): void {
    expect(TagList::fromArray(['comunidade'])->add(' COMUNIDADE ')->toArray())
        ->toBe(['comunidade']);
});

test('adding a blank tag changes nothing', function (): void {
    expect(TagList::fromArray(['comunidade'])->add('   ')->toArray())->toBe(['comunidade']);
});

test('remove returns a new list and leaves the original untouched', function (): void {
    $original = TagList::fromArray(['comunidade', 'hacktoberfest']);
    $reduced = $original->remove('HACKTOBERFEST');

    expect($reduced->toArray())->toBe(['comunidade'])
        ->and($original->toArray())->toBe(['comunidade', 'hacktoberfest']);
});

test('removing a tag that is not there changes nothing', function (): void {
    expect(TagList::fromArray(['comunidade'])->remove('evento')->toArray())
        ->toBe(['comunidade']);
});

test('the list is countable and iterable', function (): void {
    $tags = TagList::fromArray(['comunidade', 'evento']);

    expect($tags)->toHaveCount(2)
        ->and($tags->count())->toBe(2)
        ->and(iterator_to_array($tags))->toBe(['comunidade', 'evento']);
});

test('toArray is always a re-indexed list', function (): void {
    expect(array_keys(TagList::fromArray([5 => 'zulu', 9 => 'alpha'])->toArray()))
        ->toBe([0, 1]);
});
