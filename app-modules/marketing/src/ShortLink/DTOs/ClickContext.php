<?php

declare(strict_types=1);

namespace He4rt\Marketing\ShortLink\DTOs;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * A click, flattened for the queue.
 *
 * A serialized `Request` does not survive the round trip, so the job reads
 * everything it needs at the edge while the request is still alive.
 */
final readonly class ClickContext
{
    public function __construct(
        public string $shortLinkId,
        public CarbonImmutable $clickedAt,
        public ?string $ip = null,
        public ?string $userAgent = null,
        public ?string $referer = null,
        public ?string $countryCode = null,
        public ?string $utmSource = null,
        public ?string $utmMedium = null,
        public ?string $utmCampaign = null,
        public ?string $userId = null,
    ) {}

    public static function fromRequest(Request $request, string $shortLinkId): self
    {
        $user = $request->user();

        return new self(
            shortLinkId: $shortLinkId,
            clickedAt: CarbonImmutable::now(),
            ip: $request->ip(),
            userAgent: self::stringOrNull($request->userAgent()),
            referer: self::stringOrNull($request->headers->get('referer')),
            countryCode: self::countryFrom($request),
            utmSource: self::queryString($request, 'utm_source'),
            utmMedium: self::queryString($request, 'utm_medium'),
            utmCampaign: self::queryString($request, 'utm_campaign'),
            userId: $user === null ? null : (string) $user->getAuthIdentifier(),
        );
    }

    /**
     * Cloudflare sets this header. Locally it is absent and the column stays null.
     */
    private static function countryFrom(Request $request): ?string
    {
        $country = self::stringOrNull($request->headers->get('CF-IPCountry'));

        if ($country === null) {
            return null;
        }

        $country = mb_strtoupper(mb_trim($country));

        return mb_strlen($country) === 2 ? $country : null;
    }

    private static function queryString(Request $request, string $key): ?string
    {
        $value = $request->query($key);

        return is_string($value) && $value !== '' ? $value : null;
    }

    private static function stringOrNull(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return mb_trim($value) === '' ? null : $value;
    }
}
