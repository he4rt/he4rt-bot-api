<?php

declare(strict_types=1);

namespace He4rt\Contents\Data;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * @implements Arrayable<int, string>
 */
final readonly class TagList implements Arrayable, JsonSerializable
{
    /** @param list<string> $tags */
    public function __construct(
        public array $tags = [],
    ) {}

    /**
     * @param  array<array-key, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $tags = [];

        foreach ($payload as $value) {
            if (!is_string($value)) {
                continue;
            }

            $trimmed = mb_trim($value);

            if ($trimmed === '') {
                continue;
            }

            $tags[] = $trimmed;
        }

        return new self(array_values(array_unique($tags)));
    }

    /**
     * @return list<string>
     */
    public function toArray(): array
    {
        return $this->tags;
    }

    public function isEmpty(): bool
    {
        return $this->tags === [];
    }

    public function contains(string $tag): bool
    {
        return in_array($tag, $this->tags, strict: true);
    }

    /**
     * @return list<string>
     */
    public function jsonSerialize(): array
    {
        return $this->tags;
    }
}
