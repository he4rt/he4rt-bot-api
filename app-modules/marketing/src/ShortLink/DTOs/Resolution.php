<?php

declare(strict_types=1);

namespace He4rt\Marketing\ShortLink\DTOs;

use Carbon\CarbonImmutable;
use He4rt\Marketing\ShortLink\Enums\ShortLinkStatus;
use He4rt\Marketing\ShortLink\ValueObjects\UtmParameters;

/**
 * The result of one `/l/{slug}` lookup.
 *
 * A `null` status means the slug does not resolve: it is unknown or soft
 * deleted. Every other status carries the columns the edge needs, so the HTTP
 * layer never touches a model.
 */
final readonly class Resolution
{
    public function __construct(
        public ?string $id = null,
        public ?string $destinationUrl = null,
        public ?UtmParameters $utm = null,
        public ?ShortLinkStatus $status = null,
    ) {}

    public static function missing(): self
    {
        return new self();
    }

    /**
     * Evaluates the status from the cached columns on every read.
     *
     * The cache stores `expires_at` instead of a pre-computed status, so an
     * expired link starts to answer 404 without a scheduled invalidation.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function fromPayload(array $payload): self
    {
        $id = $payload['id'] ?? null;
        $destination = $payload['destination_url'] ?? null;

        if (!is_string($id) || !is_string($destination)) {
            return self::missing();
        }

        $utm = $payload['utm'] ?? [];
        $expiresAt = $payload['expires_at'] ?? null;

        return new self(
            id: $id,
            destinationUrl: $destination,
            utm: UtmParameters::fromArray(is_array($utm) ? $utm : []),
            status: self::statusFor(
                active: (bool) ($payload['active'] ?? false),
                expiresAt: is_string($expiresAt) ? CarbonImmutable::parse($expiresAt) : null,
            ),
        );
    }

    public function isRedirectable(): bool
    {
        return $this->status === ShortLinkStatus::Active
            && $this->destinationUrl !== null;
    }

    public function exists(): bool
    {
        return $this->status instanceof ShortLinkStatus;
    }

    /**
     * A disabled link stays `Disabled`, even when `expires_at` is also in the past.
     */
    private static function statusFor(bool $active, ?CarbonImmutable $expiresAt): ShortLinkStatus
    {
        if (!$active) {
            return ShortLinkStatus::Disabled;
        }

        if ($expiresAt instanceof CarbonImmutable && $expiresAt->isPast()) {
            return ShortLinkStatus::Expired;
        }

        return ShortLinkStatus::Active;
    }
}
