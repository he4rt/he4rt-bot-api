<?php

declare(strict_types=1);

namespace He4rt\Marketing\ShortLink\Casts;

use He4rt\Marketing\ShortLink\ValueObjects\TagList;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * @implements CastsAttributes<TagList, TagList|array<array-key, mixed>>
 */
final class AsTagList implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): TagList
    {
        $payload = json_decode(is_string($value) ? $value : '[]', associative: true);

        return TagList::fromArray(is_array($payload) ? $payload : []);
    }

    /**
     * @param  TagList|array<array-key, mixed>|null  $value
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        $tags = match (true) {
            $value instanceof TagList => $value,
            is_array($value) => TagList::fromArray($value),
            default => new TagList(),
        };

        return json_encode($tags->toArray(), JSON_THROW_ON_ERROR);
    }
}
