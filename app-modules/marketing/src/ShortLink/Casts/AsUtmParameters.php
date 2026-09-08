<?php

declare(strict_types=1);

namespace He4rt\Marketing\ShortLink\Casts;

use He4rt\Marketing\ShortLink\ValueObjects\UtmParameters;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * @implements CastsAttributes<UtmParameters, UtmParameters|array<array-key, mixed>>
 */
final class AsUtmParameters implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): UtmParameters
    {
        $payload = json_decode(is_string($value) ? $value : '{}', associative: true);

        return UtmParameters::fromArray(is_array($payload) ? $payload : []);
    }

    /**
     * @param  UtmParameters|array<array-key, mixed>|null  $value
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        $utm = match (true) {
            $value instanceof UtmParameters => $value,
            is_array($value) => UtmParameters::fromArray($value),
            default => new UtmParameters(),
        };

        return json_encode($utm->toArray(), JSON_THROW_ON_ERROR);
    }
}
