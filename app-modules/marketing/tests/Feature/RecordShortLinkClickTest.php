<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use He4rt\Identity\User\Models\User;
use He4rt\Marketing\ShortLink\Actions\CreateShortLink;
use He4rt\Marketing\ShortLink\DTOs\ClickContext;
use He4rt\Marketing\ShortLink\DTOs\NewShortLinkData;
use He4rt\Marketing\ShortLink\Jobs\RecordShortLinkClick;
use He4rt\Marketing\ShortLink\Models\ShortLink;
use He4rt\Marketing\ShortLink\Models\ShortLinkClick;
use Illuminate\Http\Request;

const IPHONE_USER_AGENT = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Mobile/15E148 Safari/604.1';
const DISCORD_BOT_USER_AGENT = 'Mozilla/5.0 (compatible; Discordbot/2.0; +https://discordapp.com)';

function makeClickableShortLink(): ShortLink
{
    return resolve(CreateShortLink::class)->execute(new NewShortLinkData(
        nickname: 'discord',
        destinationUrl: 'https://discord.gg/he4rt',
    ));
}

function contextFor(
    ShortLink $link,
    ?string $userAgent = IPHONE_USER_AGENT,
    ?string $ip = '187.1.2.3',
    ?string $referer = null,
    ?string $countryCode = null,
    ?string $utmSource = null,
    ?string $userId = null,
): ClickContext {
    return new ClickContext(
        shortLinkId: (string) $link->getKey(),
        clickedAt: CarbonImmutable::now(),
        ip: $ip,
        userAgent: $userAgent,
        referer: $referer,
        countryCode: $countryCode,
        utmSource: $utmSource,
        userId: $userId,
    );
}

test('a human click is classified and moves both counters', function (): void {
    $link = makeClickableShortLink();

    new RecordShortLinkClick(contextFor(
        $link,
        referer: 'https://twitter.com/he4rtdevs/status/1',
        countryCode: 'BR',
        utmSource: 'twitter',
    ))->handle();

    $click = ShortLinkClick::query()->firstOrFail();
    $link->refresh();

    expect($click->is_bot)->toBeFalse()
        ->and($click->bot_name)->toBeNull()
        ->and($click->device_type)->toBe('smartphone')
        ->and($click->os)->toBe('iOS')
        ->and($click->browser)->not->toBeNull()
        ->and($click->ip_address)->toBe('187.1.2.3')
        ->and($click->user_agent)->toBe(IPHONE_USER_AGENT)
        ->and($click->referer)->toBe('https://twitter.com/he4rtdevs/status/1')
        ->and($click->country_code)->toBe('BR')
        ->and($click->utm_source)->toBe('twitter')
        ->and($link->clicks_count)->toBe(1)
        ->and($link->human_clicks_count)->toBe(1);
});

test('an unfurl bot is recorded but kept out of the human counter', function (): void {
    $link = makeClickableShortLink();

    new RecordShortLinkClick(contextFor($link, DISCORD_BOT_USER_AGENT))->handle();

    $click = ShortLinkClick::query()->firstOrFail();
    $link->refresh();

    expect($click->is_bot)->toBeTrue()
        ->and($click->bot_name)->not->toBeNull()
        ->and($click->device_type)->toBeNull()
        ->and($click->browser)->toBeNull()
        ->and($click->os)->toBeNull()
        ->and($link->clicks_count)->toBe(1)
        ->and($link->human_clicks_count)->toBe(0);
});

test('counters add up across mixed traffic', function (): void {
    $link = makeClickableShortLink();

    foreach ([DISCORD_BOT_USER_AGENT, DISCORD_BOT_USER_AGENT, IPHONE_USER_AGENT] as $userAgent) {
        new RecordShortLinkClick(contextFor($link, $userAgent))->handle();
    }

    $link->refresh();

    expect(ShortLinkClick::query()->count())->toBe(3)
        ->and($link->clicks_count)->toBe(3)
        ->and($link->human_clicks_count)->toBe(1);
});

test('a logged in visitor is attributed, an anonymous one is not', function (): void {
    $link = makeClickableShortLink();
    $user = User::factory()->create();

    new RecordShortLinkClick(contextFor($link, userId: (string) $user->getKey()))->handle();
    new RecordShortLinkClick(contextFor($link))->handle();

    expect(ShortLinkClick::query()->whereNotNull('user_id')->count())->toBe(1)
        ->and(ShortLinkClick::query()->whereNull('user_id')->count())->toBe(1);
});

test('a missing user agent does not break the job', function (): void {
    $link = makeClickableShortLink();

    new RecordShortLinkClick(contextFor($link, userAgent: null))->handle();

    $link->refresh();

    expect(ShortLinkClick::query()->firstOrFail()->user_agent)->toBeEmpty()
        ->and($link->clicks_count)->toBe(1);
});

test('ClickContext reads the country from the Cloudflare header', function (): void {
    $request = Request::create('/l/discord-a3f9k?utm_source=twitter&utm_medium=post', server: [
        'HTTP_CF_IPCOUNTRY' => 'br',
        'HTTP_USER_AGENT' => IPHONE_USER_AGENT,
        'HTTP_REFERER' => 'https://twitter.com/he4rtdevs/status/1',
    ]);

    $context = ClickContext::fromRequest($request, 'short-link-id');

    expect($context->countryCode)->toBe('BR')
        ->and($context->shortLinkId)->toBe('short-link-id')
        ->and($context->userAgent)->toBe(IPHONE_USER_AGENT)
        ->and($context->referer)->toBe('https://twitter.com/he4rtdevs/status/1')
        ->and($context->utmSource)->toBe('twitter')
        ->and($context->utmMedium)->toBe('post')
        ->and($context->utmCampaign)->toBeNull()
        ->and($context->userId)->toBeNull();
});

test('ClickContext leaves the country null without the header', function (): void {
    $request = Request::create('/l/discord-a3f9k');
    $request->headers->remove('user-agent');

    $context = ClickContext::fromRequest($request, 'short-link-id');

    expect($context->countryCode)->toBeNull()
        ->and($context->userAgent)->toBeNull()
        ->and($context->referer)->toBeNull()
        ->and($context->utmSource)->toBeNull();
});

test('ClickContext ignores a country header Cloudflare could not resolve', function (): void {
    $request = Request::create('/l/discord-a3f9k', server: ['HTTP_CF_IPCOUNTRY' => '']);

    expect(ClickContext::fromRequest($request, 'short-link-id')->countryCode)->toBeNull();
});

test('ClickContext survives the queue serialization round trip', function (): void {
    $link = makeClickableShortLink();
    $job = new RecordShortLinkClick(contextFor($link, countryCode: 'BR'));

    /** @var RecordShortLinkClick $revived */
    $revived = unserialize(serialize($job));
    $revived->handle();

    expect(ShortLinkClick::query()->firstOrFail()->country_code)->toBe('BR');
});
