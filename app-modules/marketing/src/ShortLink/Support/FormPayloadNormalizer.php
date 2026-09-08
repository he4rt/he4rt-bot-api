<?php

declare(strict_types=1);

namespace He4rt\Marketing\ShortLink\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use He4rt\Marketing\ShortLink\ValueObjects\TagList;
use He4rt\Marketing\ShortLink\ValueObjects\UtmParameters;

/**
 * Converts a Filament payload into the typed values the DTOs accept.
 *
 * Shared by `NewShortLinkData::fromForm()` and `ShortLinkChanges::fromForm()`,
 * so the panel can send UTM fields nested (`utm.source`) or flat (`utm_source`)
 * and neither DTO has to know about form shapes.
 */
final class FormPayloadNormalizer
{
    /** @var list<string> */
    public const array UTM_KEYS = ['source', 'medium', 'campaign', 'term', 'content'];

    /**
     * @param  array<string, mixed>  $data
     */
    public static function hasUtm(array $data): bool
    {
        if (array_key_exists('utm', $data)) {
            return true;
        }

        return array_any(self::UTM_KEYS, fn (string $key) => array_key_exists('utm_'.$key, $data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function utm(array $data): ?UtmParameters
    {
        if (!self::hasUtm($data)) {
            return null;
        }

        $nested = $data['utm'] ?? null;

        if ($nested instanceof UtmParameters) {
            return $nested;
        }

        if (is_array($nested)) {
            return UtmParameters::fromArray($nested);
        }

        $flat = [];

        foreach (self::UTM_KEYS as $key) {
            $value = $data['utm_'.$key] ?? null;
            $flat[$key] = is_string($value) && $value !== '' ? $value : null;
        }

        return UtmParameters::fromArray($flat);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function tags(array $data): ?TagList
    {
        if (!array_key_exists('tags', $data)) {
            return null;
        }

        $tags = $data['tags'];

        if ($tags instanceof TagList) {
            return $tags;
        }

        return TagList::fromArray(is_array($tags) ? $tags : []);
    }

    public static function date(mixed $value): ?CarbonInterface
    {
        if ($value instanceof CarbonInterface) {
            return $value;
        }

        if (!is_string($value) || mb_trim($value) === '') {
            return null;
        }

        return CarbonImmutable::parse($value);
    }
}
