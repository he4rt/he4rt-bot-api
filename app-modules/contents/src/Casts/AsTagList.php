<?php

declare(strict_types=1);

namespace He4rt\Contents\Casts;

use He4rt\Contents\Data\TagList;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * @implements CastsAttributes<TagList, TagList|array<array-key, mixed>>
 */
final class AsTagList implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): TagList
    {
        $payload = json_decode((string) ($value ?? '[]'), associative: true);

        return TagList::fromArray(is_array($payload) ? $payload : []);
    }

    /**
     * @param  TagList|array<array-key, mixed>|null  $value
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        $tagList = match (true) {
            $value instanceof TagList => $value,
            is_array($value) => TagList::fromArray($value),
            default => new TagList(),
        };

        return json_encode($tagList->toArray(), JSON_THROW_ON_ERROR);
    }
}
