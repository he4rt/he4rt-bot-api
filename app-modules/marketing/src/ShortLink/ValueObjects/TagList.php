<?php

declare(strict_types=1);

namespace He4rt\Marketing\ShortLink\ValueObjects;

use ArrayIterator;
use Countable;
use Illuminate\Support\Str;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, string>
 */
final readonly class TagList implements Countable, IteratorAggregate
{
    /** @var list<string> */
    public array $tags;

    /**
     * @param  array<array-key, mixed>  $tags
     */
    public function __construct(array $tags = [])
    {
        $this->tags = $this->normalize($tags);
    }

    /**
     * @param  array<array-key, mixed>  $tags
     */
    public static function fromArray(array $tags): self
    {
        return new self($tags);
    }

    /**
     * @return list<string>
     */
    public function toArray(): array
    {
        return $this->tags;
    }

    public function contains(string $tag): bool
    {
        $tag = $this->normalizeOne($tag);

        return $tag !== null && in_array($tag, $this->tags, strict: true);
    }

    public function add(string $tag): self
    {
        return new self([...$this->tags, $tag]);
    }

    public function remove(string $tag): self
    {
        $tag = $this->normalizeOne($tag);

        return new self(array_filter(
            $this->tags,
            static fn (string $existing): bool => $existing !== $tag,
        ));
    }

    public function isEmpty(): bool
    {
        return $this->tags === [];
    }

    public function count(): int
    {
        return count($this->tags);
    }

    /**
     * @return Traversable<int, string>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->tags);
    }

    /**
     * Lowercase, trimmed, without empties or duplicates, and sorted. Two lists
     * with the same tags are always identical, whatever order they arrived in.
     *
     * @param  array<array-key, mixed>  $tags
     * @return list<string>
     */
    private function normalize(array $tags): array
    {
        $normalized = [];

        foreach ($tags as $tag) {
            $tag = $this->normalizeOne($tag);

            if ($tag !== null && !in_array($tag, $normalized, strict: true)) {
                $normalized[] = $tag;
            }
        }

        sort($normalized, SORT_STRING);

        return $normalized;
    }

    private function normalizeOne(mixed $tag): ?string
    {
        if (!is_string($tag) && !is_int($tag) && !is_float($tag)) {
            return null;
        }

        $tag = Str::of((string) $tag)->trim()->lower()->toString();

        return $tag === '' ? null : $tag;
    }
}
