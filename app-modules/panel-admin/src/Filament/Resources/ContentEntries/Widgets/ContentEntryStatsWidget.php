<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\ContentEntries\Widgets;

use Carbon\CarbonImmutable;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use He4rt\Contents\Models\ContentEntry;
use Illuminate\Support\Number;

class ContentEntryStatsWidget extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        return [
            $this->catalogue(),
            $this->unlinkedAuthors(),
            $this->reactions(),
            $this->lastSync(),
        ];
    }

    private function catalogue(): Stat
    {
        $total = ContentEntry::query()->count();

        return Stat::make('Artigos no acervo', Number::format($total))
            ->description('Publicações nas últimas 8 semanas')
            ->descriptionIcon(Heroicon::OutlinedCalendarDays)
            ->chart($this->publicationsPerWeek())
            ->icon(Heroicon::OutlinedNewspaper)
            ->color('primary');
    }

    /**
     * A fila de curadoria: o sync só resolve o autor quando existe identidade
     * conectada no provider. O resto depende de vínculo manual.
     */
    private function unlinkedAuthors(): Stat
    {
        $unlinked = ContentEntry::query()->whereNull('author_id')->count();

        return Stat::make('Sem autor vinculado', Number::format($unlinked))
            ->description($unlinked === 0
                ? 'Todo artigo tem dono'
                : 'Aguardando vínculo manual')
            ->descriptionIcon($unlinked === 0
                ? Heroicon::OutlinedCheckCircle
                : Heroicon::OutlinedExclamationTriangle)
            ->icon(Heroicon::OutlinedUserPlus)
            ->color($unlinked === 0 ? 'success' : 'warning');
    }

    private function reactions(): Stat
    {
        $total = (int) ContentEntry::query()->sum('reactions_count');
        $measured = ContentEntry::query()->whereNotNull('reactions_count')->count();

        $average = $measured > 0 ? (int) round($total / $measured) : 0;

        return Stat::make('Reações no acervo', Number::format($total))
            ->description(sprintf('Média de %s por artigo medido', Number::format($average)))
            ->descriptionIcon(Heroicon::OutlinedHeart)
            ->icon(Heroicon::OutlinedHandThumbUp)
            ->color('info');
    }

    private function lastSync(): Stat
    {
        /** @var CarbonImmutable|null $last */
        $last = ContentEntry::query()->max('metrics_synced_at');

        $last = $last === null ? null : CarbonImmutable::parse($last);

        $isStale = !$last instanceof CarbonImmutable || $last->lt(now()->subWeek());

        return Stat::make(
            'Última sincronização',
            $last instanceof CarbonImmutable ? $last->diffForHumans() : 'Nunca',
        )
            ->description($isStale
                ? 'Métricas paradas há mais de uma semana'
                : 'Métricas em dia')
            ->descriptionIcon($isStale
                ? Heroicon::OutlinedExclamationTriangle
                : Heroicon::OutlinedCheckCircle)
            ->icon(Heroicon::OutlinedArrowPath)
            ->color($isStale ? 'danger' : 'success');
    }

    /**
     * Publicações por semana, das oito últimas até a corrente. Semanas sem
     * artigo entram como zero: sem isso o gráfico comprime o eixo e sugere uma
     * cadência que não existiu.
     *
     * @return array<int, int>
     */
    private function publicationsPerWeek(): array
    {
        $timezone = config()->string('app.display_timezone');

        $start = now()->timezone($timezone)->startOfWeek()->subWeeks(7);

        /** @var array<string, int> $counts */
        $counts = ContentEntry::query()
            ->where('published_at', '>=', $start)
            ->selectRaw(
                "to_char(date_trunc('week', published_at AT TIME ZONE ?), 'YYYY-MM-DD') as week, count(*) as total",
                [$timezone],
            )
            ->groupBy('week')
            ->pluck('total', 'week')
            ->all();

        $series = [];

        for ($offset = 0; $offset < 8; $offset++) {
            $week = $start->copy()->addWeeks($offset)->format('Y-m-d');

            $series[] = (int) ($counts[$week] ?? 0);
        }

        return $series;
    }
}
