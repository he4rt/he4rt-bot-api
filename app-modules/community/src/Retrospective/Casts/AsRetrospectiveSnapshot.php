<?php

declare(strict_types=1);

namespace He4rt\Community\Retrospective\Casts;

use He4rt\Community\Retrospective\DTOs\RetrospectiveSnapshot;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Snapshot é nulo enquanto a edição é rascunho (só existe após o publish congelar
 * os SourceResult), então o cast é nullable nas duas pontas.
 *
 * @implements CastsAttributes<RetrospectiveSnapshot|null, RetrospectiveSnapshot|array<string, mixed>|null>
 */
final class AsRetrospectiveSnapshot implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?RetrospectiveSnapshot
    {
        if ($value === null || $value === '') {
            return null;
        }

        $payload = json_decode((string) $value, associative: true);

        return RetrospectiveSnapshot::makeFromPayload(is_array($payload) ? $payload : []);
    }

    /**
     * @param  RetrospectiveSnapshot|array<string, mixed>|null  $value
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        $snapshot = match (true) {
            $value instanceof RetrospectiveSnapshot => $value,
            is_array($value) => RetrospectiveSnapshot::makeFromPayload($value),
            default => null,
        };

        if (!$snapshot instanceof RetrospectiveSnapshot) {
            return null;
        }

        return json_encode($snapshot->toArray(), JSON_THROW_ON_ERROR);
    }
}
