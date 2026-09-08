<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Contributions\Widgets;

use Filament\Widgets\Widget;
use He4rt\PanelAdmin\Contributions\Timeline\DailyActivitySeries;

/**
 * A seleção de dias sai daqui como o evento Livewire `activity-timeline-selected`,
 * com o intervalo em datas ISO — quem quiser recortar seus próprios números escuta
 * o evento, sem conhecer a geometria do gráfico.
 */
class ActivityTimelineWidget extends Widget
{
    /**
     * Mesma janela do deck de retrospectiva, para os dois contarem a mesma história.
     */
    private const int DAYS = 32;

    protected string $view = 'panel-admin::contributions.activity-timeline';

    protected int|string|array $columnSpan = 'full';

    protected DailyActivitySeries $series;

    /**
     * Livewire resolve o boot a cada request, então a dependência entra por injeção
     * e não vira estado serializado do componente.
     */
    public function boot(DailyActivitySeries $series): void
    {
        $this->series = $series;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $series = $this->series->lastDays(self::DAYS);
        $payload = $series->toArray();

        return [
            'payload' => $payload,
            'meta' => $payload['meta'],
            'timezoneLabel' => __('panel-admin::contributions.timeline.timezones.'.$series->meta->timezone),
        ];
    }
}
