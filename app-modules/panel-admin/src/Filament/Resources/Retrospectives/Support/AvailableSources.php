<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Retrospectives\Support;

use He4rt\Community\Retrospective\Contracts\RetrospectiveSource;

/**
 * Descobre as fontes registradas (tag "retrospective.source") para o painel
 * listar e ordenar por (key, label) sem coletar dado. É a única razão de o
 * contrato expor label() estático.
 */
final class AvailableSources
{
    /**
     * @return array<string, RetrospectiveSource> key => fonte
     */
    public static function all(): array
    {
        $sources = [];

        /** @var iterable<RetrospectiveSource> $tagged */
        $tagged = app()->tagged('retrospective.source');

        foreach ($tagged as $source) {
            $sources[$source->key()] = $source;
        }

        return $sources;
    }

    /**
     * @return array<string, string> key => label
     */
    public static function map(): array
    {
        return array_map(
            static fn (RetrospectiveSource $source): string => $source->label(),
            self::all(),
        );
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::all());
    }
}
