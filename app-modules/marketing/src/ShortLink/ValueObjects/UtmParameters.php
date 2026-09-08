<?php

declare(strict_types=1);

namespace He4rt\Marketing\ShortLink\ValueObjects;

use Illuminate\Support\Str;

final readonly class UtmParameters
{
    public function __construct(
        public ?string $source = null,
        public ?string $medium = null,
        public ?string $campaign = null,
        public ?string $term = null,
        public ?string $content = null,
    ) {}

    /**
     * Accepts the canonical key (`utm_source`) and the short name (`source`).
     * The panel form and the stored jsonb use different spellings.
     *
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            source: self::read($data, 'source'),
            medium: self::read($data, 'medium'),
            campaign: self::read($data, 'campaign'),
            term: self::read($data, 'term'),
            content: self::read($data, 'content'),
        );
    }

    /**
     * Builds the final URL. Precedence, strongest first:
     *
     * 1. the query already in the destination URL, which staff wrote on purpose
     * 2. the query the visitor sent to the short URL
     * 3. the UTM configured on the link, which fills only what is missing
     *
     * @param  array<string, mixed>  $incoming  query sent to the short URL
     */
    public function appendTo(string $destination, array $incoming = []): string
    {
        $configured = $this->filled();
        $carried = $this->filled($incoming);

        $hasNothingToAppend = $configured === [] && $carried === [];

        if ($hasNothingToAppend) {
            return $destination;
        }

        $components = parse_url($destination);

        if (!is_array($components)) {
            return $destination;
        }

        $existing = [];
        parse_str(is_string($components['query'] ?? null) ? $components['query'] : '', $existing);

        $query = $existing;

        foreach ([$carried, $configured] as $weaker) {
            foreach ($weaker as $key => $value) {
                if (!array_key_exists($key, $query)) {
                    $query[$key] = $value;
                }
            }
        }

        if ($query === []) {
            return $destination;
        }

        $base = Str::of($destination)->before('#')->before('?')->toString();

        $fragment = is_string($components['fragment'] ?? null)
            ? '#'.$components['fragment']
            : '';

        return $base.'?'.http_build_query($query, encoding_type: PHP_QUERY_RFC3986).$fragment;
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'utm_source' => $this->source,
            'utm_medium' => $this->medium,
            'utm_campaign' => $this->campaign,
            'utm_term' => $this->term,
            'utm_content' => $this->content,
        ];
    }

    public function isEmpty(): bool
    {
        return $this->filled() === [];
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    private static function read(array $data, string $name): ?string
    {
        $canonical = $data['utm_'.$name] ?? null;

        return self::normalize($canonical ?? $data[$name] ?? null);
    }

    private static function normalize(mixed $value): ?string
    {
        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            return null;
        }

        $value = mb_trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * Only the pairs that have a value, ready for a query string.
     *
     * @param  array<array-key, mixed>|null  $source
     * @return array<string, string>
     */
    private function filled(?array $source = null): array
    {
        $filled = [];

        foreach ($source ?? $this->toArray() as $key => $value) {
            $key = (string) $key;

            if (is_array($value)) {
                continue;
            }

            $value = self::normalize($value);

            if ($value !== null) {
                $filled[$key] = $value;
            }
        }

        return $filled;
    }
}
