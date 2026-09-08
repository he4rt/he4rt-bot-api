<?php

declare(strict_types=1);

namespace He4rt\Marketing\ShortLink\Jobs;

use DeviceDetector\DeviceDetector;
use He4rt\Marketing\ShortLink\DTOs\ClickContext;
use He4rt\Marketing\ShortLink\Models\ShortLink;
use He4rt\Marketing\ShortLink\Models\ShortLinkClick;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Records one click, off the redirect path.
 *
 * User-Agent parsing, the INSERT and the counter updates all happen here, so
 * the redirect answers in milliseconds and a traffic spike only makes the queue
 * longer.
 *
 * Preview crawlers (Discord, WhatsApp, Twitter, Slack) can produce several hits
 * before a person clicks. Their rows are kept but flagged `is_bot`, and only
 * people increment `human_clicks_count`.
 */
#[Backoff([10, 30, 120])]
#[Tries(tries: 3)]
final class RecordShortLinkClick implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;

    public function __construct(private readonly ClickContext $context) {}

    public function handle(): void
    {
        $detector = new DeviceDetector($this->context->userAgent ?? '');
        $detector->parse();

        $isBot = $detector->isBot();

        ShortLinkClick::query()->create([
            'short_link_id' => $this->context->shortLinkId,
            'clicked_at' => $this->context->clickedAt,
            // Raw personal data, with no retention policy. See ADR-0003.
            'ip_address' => $this->context->ip ?? '',
            'user_agent' => $this->context->userAgent ?? '',
            'referer' => $this->context->referer,
            'country_code' => $this->context->countryCode,
            'is_bot' => $isBot,
            'bot_name' => $isBot ? $this->botName($detector) : null,
            'device_type' => $isBot ? null : $detector->getDeviceName(),
            'browser' => $isBot ? null : $this->stringOrNull($detector->getClient('name')),
            'os' => $isBot ? null : $this->stringOrNull($detector->getOs('name')),
            'utm_source' => $this->context->utmSource,
            'utm_medium' => $this->context->utmMedium,
            'utm_campaign' => $this->context->utmCampaign,
            'user_id' => $this->context->userId,
        ]);

        $counters = ['clicks_count' => 1];

        if (!$isBot) {
            $counters['human_clicks_count'] = 1;
        }

        ShortLink::query()
            ->whereKey($this->context->shortLinkId)
            ->incrementEach($counters);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Failed to record short link click.', [
            'short_link_id' => $this->context->shortLinkId,
            'clicked_at' => $this->context->clickedAt->toIso8601String(),
            'exception' => $exception?->getMessage(),
        ]);
    }

    private function botName(DeviceDetector $detector): ?string
    {
        $bot = $detector->getBot();

        if (!is_array($bot)) {
            return null;
        }

        return $this->stringOrNull($bot['name'] ?? null);
    }

    private function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
