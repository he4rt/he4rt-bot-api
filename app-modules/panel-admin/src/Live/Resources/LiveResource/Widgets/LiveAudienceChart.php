<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Live\Resources\LiveResource\Widgets;

use Filament\Widgets\ChartWidget;
use He4rt\Live\Models\Live;
use He4rt\Live\Models\LiveViewerSample;

class LiveAudienceChart extends ChartWidget
{
    /** Set by `Filament\Schemas\Components\Livewire::getComponentProperties()`. */
    public ?Live $record = null;

    protected ?string $pollingInterval = null;

    public function getHeading(): string
    {
        return 'Audiência';
    }

    protected function getType(): string
    {
        return 'line';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $timezone = config('app.display_timezone');

        $samples = LiveViewerSample::query()
            ->where('live_id', $this->record?->getKey())
            ->oldest('sampled_at')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Espectadores',
                    'data' => $samples->pluck('viewers')->all(),
                    'borderColor' => '#782bf1',
                    'backgroundColor' => 'rgba(120, 43, 241, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $samples
                ->map(fn (LiveViewerSample $sample): string => $sample->sampled_at->timezone($timezone)->format('d/m H:i'))
                ->all(),
        ];
    }
}
