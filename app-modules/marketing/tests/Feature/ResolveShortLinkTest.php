<?php

declare(strict_types=1);

use Carbon\CarbonInterface;
use He4rt\Marketing\ShortLink\Actions\CreateShortLink;
use He4rt\Marketing\ShortLink\Actions\ResolveShortLink;
use He4rt\Marketing\ShortLink\Actions\UpdateShortLink;
use He4rt\Marketing\ShortLink\DTOs\NewShortLinkData;
use He4rt\Marketing\ShortLink\DTOs\ShortLinkChanges;
use He4rt\Marketing\ShortLink\Enums\ShortLinkStatus;
use He4rt\Marketing\ShortLink\Models\ShortLink;
use He4rt\Marketing\ShortLink\Observers\ShortLinkObserver;
use He4rt\Marketing\ShortLink\Support\ShortLinkCache;
use He4rt\Marketing\ShortLink\ValueObjects\UtmParameters;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

function makeResolvableShortLink(
    string $destinationUrl = 'https://discord.gg/he4rt',
    ?UtmParameters $utm = null,
    bool $active = true,
    ?CarbonInterface $expiresAt = null,
): ShortLink {
    return resolve(CreateShortLink::class)->execute(new NewShortLinkData(
        nickname: 'discord',
        destinationUrl: $destinationUrl,
        utm: $utm,
        active: $active,
        expiresAt: $expiresAt,
    ));
}

test('an active link resolves as redirectable', function (): void {
    $link = makeResolvableShortLink(utm: UtmParameters::fromArray(['source' => 'discord']));

    $resolution = resolve(ResolveShortLink::class)->execute($link->slug);

    expect($resolution->status)->toBe(ShortLinkStatus::Active)
        ->and($resolution->isRedirectable())->toBeTrue()
        ->and($resolution->id)->toBe((string) $link->getKey())
        ->and($resolution->destinationUrl)->toBe('https://discord.gg/he4rt')
        ->and($resolution->utm->source)->toBe('discord');
});

test('an expired link resolves but is not redirectable', function (): void {
    $link = makeResolvableShortLink(expiresAt: now()->subDay());

    $resolution = resolve(ResolveShortLink::class)->execute($link->slug);

    expect($resolution->status)->toBe(ShortLinkStatus::Expired)
        ->and($resolution->isRedirectable())->toBeFalse();
});

test('a disabled link resolves as disabled even when the expiry is in the future', function (): void {
    $link = makeResolvableShortLink(active: false, expiresAt: now()->addYear());

    $resolution = resolve(ResolveShortLink::class)->execute($link->slug);

    expect($resolution->status)->toBe(ShortLinkStatus::Disabled)
        ->and($resolution->isRedirectable())->toBeFalse();
});

test('expiry is evaluated on read, not frozen into the cache', function (): void {
    $link = makeResolvableShortLink(expiresAt: now()->addMinutes(5));

    expect(resolve(ResolveShortLink::class)->execute($link->slug)->status)
        ->toBe(ShortLinkStatus::Active);

    Date::setTestNow(now()->addMinutes(10));

    expect(resolve(ResolveShortLink::class)->execute($link->slug)->status)
        ->toBe(ShortLinkStatus::Expired);
});

test('an unknown slug resolves to nothing and is not re-queried within the negative ttl', function (): void {
    $action = resolve(ResolveShortLink::class);

    $resolution = $action->execute('does-not-exist');

    expect($resolution->status)->toBeNull()
        ->and($resolution->isRedirectable())->toBeFalse()
        ->and($resolution->exists())->toBeFalse();

    DB::enableQueryLog();
    DB::flushQueryLog();

    $action->execute('does-not-exist');

    expect(DB::getQueryLog())->toBeEmpty();

    DB::disableQueryLog();
});

test('the negative sentinel expires after 60 seconds', function (): void {
    $action = resolve(ResolveShortLink::class);
    $action->execute('ghost-slug');

    Date::setTestNow(now()->addSeconds(61));

    DB::enableQueryLog();
    DB::flushQueryLog();

    $action->execute('ghost-slug');

    expect(DB::getQueryLog())->not->toBeEmpty();

    DB::disableQueryLog();
});

test('a soft deleted link is indistinguishable from an unknown slug', function (): void {
    $link = makeResolvableShortLink();
    $slug = $link->slug;

    $link->delete();
    ShortLinkCache::forget($slug);

    expect(resolve(ResolveShortLink::class)->execute($slug)->status)->toBeNull()
        ->and(resolve(ResolveShortLink::class)->execute('never-existed-x1y2z')->status)->toBeNull();
});

test('a resolved link is served from cache on the second read', function (): void {
    $link = makeResolvableShortLink();

    resolve(ResolveShortLink::class)->execute($link->slug);

    DB::enableQueryLog();
    DB::flushQueryLog();

    resolve(ResolveShortLink::class)->execute($link->slug);

    expect(DB::getQueryLog())->toBeEmpty();

    DB::disableQueryLog();
});

test('editing the destination is visible on the very next resolution', function (): void {
    $link = makeResolvableShortLink('https://discord.gg/old');

    expect(resolve(ResolveShortLink::class)->execute($link->slug)->destinationUrl)
        ->toBe('https://discord.gg/old');

    resolve(UpdateShortLink::class)->execute($link, ShortLinkChanges::make(
        destinationUrl: 'https://discord.gg/new',
    ));

    expect(resolve(ResolveShortLink::class)->execute($link->slug)->destinationUrl)
        ->toBe('https://discord.gg/new');
});

test('the observer drops the cached entry on save, delete and restore', function (): void {
    ShortLink::observe(ShortLinkObserver::class);

    $link = makeResolvableShortLink();

    foreach ([fn () => $link->forceFill(['active' => false])->save(), $link->delete(...), $link->restore(...)] as $write) {
        resolve(ResolveShortLink::class)->execute($link->slug);
        expect(ShortLinkCache::has($link->slug))->toBeTrue();

        $write();

        expect(ShortLinkCache::has($link->slug))->toBeFalse();
    }
});

test('the module wires the observer, so a write outside the Action still invalidates', function (): void {
    $link = makeResolvableShortLink();

    resolve(ResolveShortLink::class)->execute($link->slug);

    $link->forceFill(['active' => false])->save();

    expect(resolve(ResolveShortLink::class)->execute($link->slug)->status)
        ->toBe(ShortLinkStatus::Disabled);
});
