<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Livewire\Events;

use He4rt\Events\Event\Models\Event;
use He4rt\PanelApp\Pages\EventPage;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class EventsList extends Component
{
    /** @return Collection<int, Event> */
    #[Computed]
    public function events(): Collection
    {
        return Event::query()
            ->with('enrollmentPolicy')
            ->active()
            ->orderBy('starts_at')
            ->get();
    }

    public function eventUrl(Event $event): string
    {
        return EventPage::getUrl(['record' => $event->getKey()]);
    }

    public function render(): View
    {
        return view('panel-app::livewire.events.events-list');
    }
}
