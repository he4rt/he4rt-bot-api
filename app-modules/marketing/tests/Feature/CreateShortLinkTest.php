<?php

declare(strict_types=1);

use He4rt\Identity\User\Models\User;
use He4rt\Marketing\ShortLink\Actions\CreateShortLink;
use He4rt\Marketing\ShortLink\DTOs\NewShortLinkData;
use He4rt\Marketing\ShortLink\Exceptions\InvalidDestinationUrl;
use He4rt\Marketing\ShortLink\Models\ShortLink;
use He4rt\Marketing\ShortLink\Models\ShortLinkDestination;
use He4rt\Marketing\ShortLink\Support\SlugGenerator;
use He4rt\Marketing\ShortLink\ValueObjects\TagList;
use He4rt\Marketing\ShortLink\ValueObjects\UtmParameters;

test('creates the link with a generated slug and the staff apelido as base', function (): void {
    $link = resolve(CreateShortLink::class)->execute(new NewShortLinkData(
        nickname: 'Discord',
        destinationUrl: 'https://discord.gg/he4rt',
    ));

    expect($link->slug)->toMatch('/^discord-[0-9a-z]{5}$/')
        ->and($link->base_slug)->toBe('discord')
        ->and($link->destination_url)->toBe('https://discord.gg/he4rt')
        ->and($link->active)->toBeTrue()
        ->and($link->clicks_count)->toBe(0)
        ->and($link->human_clicks_count)->toBe(0);
});

test('creates exactly one open destination interval', function (): void {
    $link = resolve(CreateShortLink::class)->execute(new NewShortLinkData(
        nickname: 'Discord',
        destinationUrl: 'https://discord.gg/he4rt',
        utm: UtmParameters::fromArray(['source' => 'discord']),
    ));

    $destinations = ShortLinkDestination::query()
        ->where('short_link_id', $link->getKey())
        ->get();

    expect($destinations)->toHaveCount(1)
        ->and($destinations->first()->destination_url)->toBe('https://discord.gg/he4rt')
        ->and($destinations->first()->valid_until)->toBeNull()
        ->and($destinations->first()->valid_from)->not->toBeNull();
});

test('persists tags, utm, expiry and the author', function (): void {
    $user = User::factory()->create();

    $link = resolve(CreateShortLink::class)->execute(new NewShortLinkData(
        nickname: 'Hacktoberfest 2026',
        destinationUrl: 'https://github.com/he4rt',
        utm: UtmParameters::fromArray(['source' => 'discord', 'medium' => 'post']),
        tags: TagList::fromArray(['comunidade', 'hacktoberfest']),
        expiresAt: now()->addMonth(),
        createdBy: (string) $user->getKey(),
    ));

    $link->refresh();

    expect($link->utm->source)->toBe('discord')
        ->and($link->utm->medium)->toBe('post')
        ->and($link->tags->contains('comunidade'))->toBeTrue()
        ->and($link->expires_at)->not->toBeNull()
        ->and($link->created_by)->toBe((string) $user->getKey());
});

test('rejects a destination that is not http(s) and writes nothing', function (string $url): void {
    expect(fn () => resolve(CreateShortLink::class)->execute(new NewShortLinkData(
        nickname: 'exploit',
        destinationUrl: $url,
    )))->toThrow(InvalidDestinationUrl::class);

    expect(ShortLink::query()->count())->toBe(0)
        ->and(ShortLinkDestination::query()->count())->toBe(0);
})->with([
    'javascript:alert(1)',
    'data:text/html;base64,PHNjcmlwdD4=',
    'file:///etc/passwd',
]);

test('two links created from the same apelido coexist', function (): void {
    $action = resolve(CreateShortLink::class);

    $first = $action->execute(new NewShortLinkData('discord', 'https://discord.gg/one'));
    $second = $action->execute(new NewShortLinkData('discord', 'https://discord.gg/two'));

    expect($first->slug)->not->toBe($second->slug)
        ->and($first->base_slug)->toBe($second->base_slug)
        ->and(ShortLink::query()->count())->toBe(2);
});

test('fromForm builds the same link a Filament payload would', function (): void {
    $link = resolve(CreateShortLink::class)->execute(NewShortLinkData::fromForm([
        'nickname' => 'Discord',
        'destination_url' => 'https://discord.gg/he4rt',
        'tags' => ['comunidade'],
        'utm_source' => 'twitter',
        'expires_at' => null,
    ]));

    expect($link->slug)->toStartWith(SlugGenerator::base('Discord').'-')
        ->and($link->utm->source)->toBe('twitter')
        ->and($link->tags->contains('comunidade'))->toBeTrue()
        ->and($link->expires_at)->toBeNull();
});
