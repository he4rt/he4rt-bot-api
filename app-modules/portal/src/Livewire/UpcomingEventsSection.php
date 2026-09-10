<?php

declare(strict_types=1);

namespace He4rt\Portal\Livewire;

use Carbon\Carbon;
use He4rt\Events\Event\Models\Event;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class UpcomingEventsSection extends Component
{
    /**
     * @return Collection<int, array{event: Event, occurrence: Carbon}>
     */
    #[Computed]
    public function upcomingEvents(): Collection
    {
        return $this->fetchUpcomingEvents();
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function schemaOrg(): array
    {
        $events = $this->upcomingEvents()
            ->map(function (array $item): array {
                $event = $item['event'];
                $occurrence = $item['occurrence'];

                return [
                    '@type' => 'Event',
                    'name' => $event->title,
                    'description' => $event->description,
                    'startDate' => $occurrence->toIso8601String(),
                    'endDate' => $event->ends_at->toIso8601String(),
                    'eventStatus' => 'https://schema.org/EventScheduled',
                    'eventAttendanceMode' => $this->isOffline($event)
                        ? 'https://schema.org/OfflineEventAttendanceMode'
                        : 'https://schema.org/OnlineEventAttendanceMode',
                    'location' => $this->isOffline($event)
                        ? ['@type' => 'Place', 'name' => $event->location]
                        : ['@type' => 'VirtualLocation', 'url' => url('/app/events/'.$event->id)],
                    'url' => url('/app/events/'.$event->id),
                    ...($event->getFirstMediaUrl('cover') ? ['image' => $event->getFirstMediaUrl('cover')] : []),
                ];
            })
            ->values();

        return [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'itemListElement' => $events
                ->map(fn (array $event, int $index): array => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'item' => $event,
                ])
                ->all(),
        ];
    }

    public function render(): View
    {
        return view('portal::sections.upcoming-events');
    }

    /**
     * @return Collection<int, Event>
     */
    private function activeEvents(): Collection
    {
        $query = fn (): Collection => Event::query()
            ->published()
            ->upcoming()
            ->orderBy('starts_at')
            ->get();

        if (!app()->isProduction()) {
            return $query();
        }

        return Cache::remember('portal:upcoming-events', now()->addHour(), $query);
    }

    /**
     * @return Collection<int, array{event: Event, occurrence: Carbon}>
     */
    private function fetchUpcomingEvents(): Collection
    {
        return $this->activeEvents()
            ->map(fn (Event $event): array => [
                'event' => $event,
                'occurrence' => $event->starts_at,
            ]);
    }

    private function isOffline(Event $event): bool
    {
        if ($event->location === null) {
            return false;
        }

        return !str_contains(mb_strtolower((string) $event->location), 'online');
    }
}
