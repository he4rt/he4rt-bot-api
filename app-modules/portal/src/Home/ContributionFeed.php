<?php

declare(strict_types=1);

namespace He4rt\Portal\Home;

use Carbon\CarbonImmutable;
use He4rt\Activity\Tracking\Enums\ActivityType;
use He4rt\Activity\Tracking\Models\Interaction;
use He4rt\Portal\Home\DTOs\ContributionMonth;
use He4rt\Portal\Home\DTOs\ContributionPanorama;
use He4rt\Portal\Home\DTOs\ContributionSlice;

/**
 * Read model do panorama de contribuições da home.
 *
 * A fonte é o tracking do módulo `activity`, que registra o trabalho de quem
 * conectou uma identidade externa. Aqui só entra o que o admin não ocultou.
 */
final class ContributionFeed
{
    private const int TIMELINE_MONTHS = 12;

    public function panorama(): ContributionPanorama
    {
        $total = Interaction::query()->visible()->count();

        return new ContributionPanorama(
            total: $total,
            people: Interaction::query()->visible()->distinct()->count('user_id'),
            since: $this->firstContributionAt(),
            composition: $this->composition($total),
            timeline: $this->timeline(),
        );
    }

    private function firstContributionAt(): ?CarbonImmutable
    {
        $since = Interaction::query()->visible()->min('occurred_at');

        return is_string($since) ? CarbonImmutable::parse($since)->setTimezone($this->timezone()) : null;
    }

    /**
     * Ordenado por volume: a barra vai do maior para o menor, e um tipo sem
     * nenhuma contribuição não vira segmento invisível.
     *
     * @return list<ContributionSlice>
     */
    private function composition(int $total): array
    {
        $counts = Interaction::query()
            ->visible()
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->toBase()
            ->pluck('total', 'type');

        $slices = [];

        foreach (ActivityType::cases() as $type) {
            $count = (int) ($counts[$type->value] ?? 0);

            if ($count > 0) {
                $slices[] = ContributionSlice::fromType($type, $count, $total);
            }
        }

        usort($slices, static fn (ContributionSlice $a, ContributionSlice $b): int => $b->count <=> $a->count);

        return $slices;
    }

    /**
     * Os doze meses até o atual, com os vazios preenchidos — um buraco na série
     * contaria uma história diferente da que os dados contam.
     *
     * @return list<ContributionMonth>
     */
    private function timeline(): array
    {
        $first = CarbonImmutable::now($this->timezone())
            ->startOfMonth()
            ->subMonths(self::TIMELINE_MONTHS - 1);

        $counts = $this->countByMonthSince($first);

        $months = [];

        for ($offset = 0; $offset < self::TIMELINE_MONTHS; $offset++) {
            $month = $first->addMonths($offset)->format('Y-m');
            $months[$month] = $counts[$month] ?? 0;
        }

        $peak = max($months);

        return array_map(
            static fn (string $key, int $count): ContributionMonth => ContributionMonth::make(
                CarbonImmutable::parse($key.'-01'),
                $count,
                $peak,
            ),
            array_keys($months),
            $months,
        );
    }

    /**
     * `occurred_at` é timestamptz em UTC; o mês tem de ser o do fuso de exibição,
     * senão a contribuição da madrugada cai no mês errado.
     *
     * @return array<string, int>
     */
    private function countByMonthSince(CarbonImmutable $first): array
    {
        $rows = Interaction::query()
            ->visible()
            ->where('occurred_at', '>=', $first->utc())
            ->selectRaw("to_char(occurred_at at time zone ?, 'YYYY-MM') as month, count(*) as total", [$this->timezone()])
            ->groupBy('month')
            ->toBase()
            ->get();

        $counts = [];

        foreach ($rows as $row) {
            if (is_string($row->month) && is_numeric($row->total)) {
                $counts[$row->month] = (int) $row->total;
            }
        }

        return $counts;
    }

    private function timezone(): string
    {
        return config()->string('app.display_timezone');
    }
}
