<?php

declare(strict_types=1);

namespace He4rt\Community\Retrospective\Casts;

use He4rt\Community\Retrospective\DTOs\DeckConfig;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * @implements CastsAttributes<DeckConfig, DeckConfig|array<string, mixed>>
 */
final class AsDeckConfig implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): DeckConfig
    {
        $payload = json_decode((string) ($value ?? '{}'), associative: true);

        return DeckConfig::makeFromPayload(is_array($payload) ? $payload : []);
    }

    /**
     * @param  DeckConfig|array<string, mixed>|null  $value
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        $config = match (true) {
            $value instanceof DeckConfig => $value,
            is_array($value) => DeckConfig::makeFromPayload($value),
            default => new DeckConfig(),
        };

        return json_encode($config->toArray(), JSON_THROW_ON_ERROR);
    }
}
