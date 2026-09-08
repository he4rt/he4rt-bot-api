<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Marketing\Resources\ShortLinks\Widgets;

use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;
use He4rt\Marketing\ShortLink\Models\ShortLink;
use He4rt\Marketing\ShortLink\Models\ShortLinkClick;
use Illuminate\Database\Eloquent\Builder;

class ClicksOverTimeChart extends ChartWidget
{
    /** Set by `Filament\Schemas\Components\Livewire::getComponentProperties()`. */
    public ?ShortLink $record = null;

    /**
     * Set at mount by the page. A Livewire island only accepts serializable
     * data, so the filter cannot arrive through `pageFilters`. The island's
     * dynamic `key()` is what remounts this widget with a new value.
     */
    public bool $includeBots = false;

    public ?string $filter = '30d';

    protected ?string $maxHeight = '240px';

    protected ?string $pollingInterval = null;

    public function getHeading(): string
    {
        return __('panel-admin::marketing.short_links.widgets.clicks_over_time.heading');
    }

    public function getDescription(): ?string
    {
        if ($this->hasClicks()) {
            return null;
        }

        return trans('panel-admin::marketing.short_links.widgets.clicks_over_time.empty');
    }

    protected function getType(): string
    {
        return 'line';
    }

    /**
     * @return array<string, string>
     */
    protected function getFilters(): ?array
    {
        // The `d` suffix keeps the keys strings: '7' would be cast to an int.
        return [
            '7d' => trans('panel-admin::marketing.short_links.widgets.clicks_over_time.ranges.7'),
            '30d' => trans('panel-admin::marketing.short_links.widgets.clicks_over_time.ranges.30'),
            '90d' => trans('panel-admin::marketing.short_links.widgets.clicks_over_time.ranges.90'),
        ];
    }

    /**
     * The series is zero-filled across the whole range, so a link with no
     * clicks draws a flat line instead of an empty chart.
     *
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $days = $this->rangeInDays();
        $timezone = config('app.display_timezone');
        $start = CarbonImmutable::now($timezone)->subDays($days - 1)->startOfDay();

        $totals = $this->clicksQuery()
            ->where('clicked_at', '>=', $start->utc())
            // Convert once: a double `AT TIME ZONE` moves the day by three hours.
            ->selectRaw('(clicked_at AT TIME ZONE ?)::date AS day', [$timezone])
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $labels = [];
        $data = [];

        for ($offset = 0; $offset < $days; $offset++) {
            $day = $start->addDays($offset);

            $labels[] = $day->format('d/m');
            $data[] = (int) ($totals->get($day->format('Y-m-d')) ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => __('panel-admin::marketing.short_links.widgets.clicks_over_time.dataset'),
                    'data' => $data,
                    'borderColor' => '#782bf1',
                    'backgroundColor' => 'rgba(120, 43, 241, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    /**
     * `$this->filter` comes from the browser. The `match` default is what stops
     * a forged value from reaching the query.
     */
    private function rangeInDays(): int
    {
        return match ($this->filter) {
            '7d' => 7,
            '90d' => 90,
            default => 30,
        };
    }

    private function hasClicks(): bool
    {
        return $this->record instanceof ShortLink && $this->clicksQuery()->exists();
    }

    /** @return Builder<ShortLinkClick> */
    private function clicksQuery(): Builder
    {
        $query = ShortLinkClick::query()
            ->where('short_link_id', $this->record?->getKey());

        if (!$this->includeBots) {
            $query->where('is_bot', operator: false);
        }

        return $query;
    }
}
