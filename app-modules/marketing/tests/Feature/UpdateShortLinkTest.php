<?php

declare(strict_types=1);

use He4rt\Marketing\ShortLink\Actions\CreateShortLink;
use He4rt\Marketing\ShortLink\Actions\UpdateShortLink;
use He4rt\Marketing\ShortLink\DTOs\NewShortLinkData;
use He4rt\Marketing\ShortLink\DTOs\ShortLinkChanges;
use He4rt\Marketing\ShortLink\Exceptions\InvalidDestinationUrl;
use He4rt\Marketing\ShortLink\Models\ShortLink;
use He4rt\Marketing\ShortLink\Models\ShortLinkDestination;
use He4rt\Marketing\ShortLink\ValueObjects\TagList;
use He4rt\Marketing\ShortLink\ValueObjects\UtmParameters;

function makeUpdatableShortLink(string $destination = 'https://discord.gg/old'): ShortLink
{
    return resolve(CreateShortLink::class)->execute(new NewShortLinkData(
        nickname: 'discord',
        destinationUrl: $destination,
        tags: TagList::fromArray(['comunidade']),
    ));
}

test('changing only the tags creates no history row', function (): void {
    $link = makeUpdatableShortLink();

    resolve(UpdateShortLink::class)->execute($link, ShortLinkChanges::make(
        tags: TagList::fromArray(['comunidade', 'eventos']),
    ));

    expect(ShortLinkDestination::query()->where('short_link_id', $link->getKey())->count())->toBe(1)
        ->and($link->fresh()->tags->contains('eventos'))->toBeTrue();
});

test('changing the destination closes the previous interval and opens a new one', function (): void {
    $link = makeUpdatableShortLink('https://discord.gg/old');

    resolve(UpdateShortLink::class)->execute($link, ShortLinkChanges::make(
        destinationUrl: 'https://discord.gg/new',
    ));

    $destinations = ShortLinkDestination::query()
        ->where('short_link_id', $link->getKey())
        ->orderBy('valid_from')
        ->get();

    $previous = $destinations->first();
    $current = $destinations->last();

    expect($destinations)->toHaveCount(2)
        ->and($previous->destination_url)->toBe('https://discord.gg/old')
        ->and($previous->valid_until)->not->toBeNull()
        ->and($current->destination_url)->toBe('https://discord.gg/new')
        ->and($current->valid_until)->toBeNull()
        ->and($previous->valid_until->equalTo($current->valid_from))->toBeTrue()
        ->and($link->fresh()->destination_url)->toBe('https://discord.gg/new');
});

test('only one interval is ever open', function (): void {
    $link = makeUpdatableShortLink('https://a.example.com');
    $action = resolve(UpdateShortLink::class);

    $action->execute($link, ShortLinkChanges::make(destinationUrl: 'https://b.example.com'));
    $action->execute($link->fresh(), ShortLinkChanges::make(destinationUrl: 'https://c.example.com'));

    $open = ShortLinkDestination::query()
        ->where('short_link_id', $link->getKey())
        ->whereNull('valid_until')
        ->get();

    expect($open)->toHaveCount(1)
        ->and($open->first()->destination_url)->toBe('https://c.example.com')
        ->and(ShortLinkDestination::query()->where('short_link_id', $link->getKey())->count())->toBe(3);
});

test('changing only the utm also versions the destination', function (): void {
    $link = makeUpdatableShortLink();

    resolve(UpdateShortLink::class)->execute($link, ShortLinkChanges::make(
        utm: UtmParameters::fromArray(['source' => 'twitter']),
    ));

    expect(ShortLinkDestination::query()->where('short_link_id', $link->getKey())->count())->toBe(2);
});

test('rewriting the destination with the same value creates no history row', function (): void {
    $link = makeUpdatableShortLink('https://discord.gg/same');

    resolve(UpdateShortLink::class)->execute($link, ShortLinkChanges::make(
        destinationUrl: 'https://discord.gg/same',
    ));

    expect(ShortLinkDestination::query()->where('short_link_id', $link->getKey())->count())->toBe(1);
});

test('a failure mid-transaction leaves no second open interval', function (): void {
    $link = makeUpdatableShortLink('https://discord.gg/old');

    ShortLinkDestination::creating(function (): never {
        throw new RuntimeException('boom');
    });

    expect(fn () => resolve(UpdateShortLink::class)->execute($link, ShortLinkChanges::make(
        destinationUrl: 'https://discord.gg/new',
    )))->toThrow(RuntimeException::class, 'boom');

    $destinations = ShortLinkDestination::query()
        ->where('short_link_id', $link->getKey())
        ->get();

    expect($destinations)->toHaveCount(1)
        ->and($destinations->first()->valid_until)->toBeNull()
        ->and($link->fresh()->destination_url)->toBe('https://discord.gg/old');
});

test('an invalid destination is refused before anything is written', function (): void {
    $link = makeUpdatableShortLink('https://discord.gg/old');

    expect(fn () => resolve(UpdateShortLink::class)->execute($link, ShortLinkChanges::make(
        destinationUrl: 'javascript:alert(1)',
    )))->toThrow(InvalidDestinationUrl::class);

    expect($link->fresh()->destination_url)->toBe('https://discord.gg/old')
        ->and(ShortLinkDestination::query()->where('short_link_id', $link->getKey())->count())->toBe(1);
});

test('fromForm treats a submitted null expiry as an explicit clear', function (): void {
    $link = makeUpdatableShortLink();
    $link->forceFill(['expires_at' => now()->addDay()])->save();

    resolve(UpdateShortLink::class)->execute($link, ShortLinkChanges::fromForm([
        'expires_at' => null,
    ]));

    expect($link->fresh()->expires_at)->toBeNull()
        ->and(ShortLinkDestination::query()->where('short_link_id', $link->getKey())->count())->toBe(1);
});
