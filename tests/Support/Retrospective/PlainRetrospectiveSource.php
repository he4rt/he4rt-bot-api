<?php

declare(strict_types=1);

namespace Tests\Support\Retrospective;

use He4rt\Community\Retrospective\Contracts\RetrospectiveSource;
use He4rt\Community\Retrospective\DTOs\HeadlineMetrics;
use He4rt\Community\Retrospective\DTOs\Period;
use He4rt\Community\Retrospective\DTOs\SourceFilters;
use He4rt\Community\Retrospective\DTOs\SourceResult;

/**
 * Fonte que implementa APENAS RetrospectiveSource, sem CuratableSource. Existe
 * para provar a garantia de interface segregada do ADR-0002: uma fonte que não
 * sabe se descrever continua entrando na timeline com ordem e on/off, só sem
 * catálogo de slides nem picker de exclusions.
 *
 * Ambas as fontes reais (GitHub, Discord) curam, então essa metade do contrato
 * só é observável com um duplo.
 */
final class PlainRetrospectiveSource implements RetrospectiveSource
{
    public const string KEY = 'plain';

    public const string LABEL = 'Fonte Crua';

    public static function register(): void
    {
        app()->tag([self::class], 'retrospective.source');
    }

    public function key(): string
    {
        return self::KEY;
    }

    public function label(): string
    {
        return self::LABEL;
    }

    public function collect(Period $period, SourceFilters $filters): SourceResult
    {
        return new SourceResult($this->key(), $this->label(), new HeadlineMetrics(), []);
    }
}
