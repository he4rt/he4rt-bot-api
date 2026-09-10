<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Livewire\Events;

use He4rt\Events\Enrollment\Models\Enrollment;
use He4rt\Events\Event\Models\Event;
use He4rt\PanelApp\Pages\EventPage;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class MyEventsList extends Component
{
    /** @return Collection<int, Enrollment> */
    #[Computed]
    public function enrollments(): Collection
    {
        return Enrollment::query()
            ->with(['event'])
            ->where('user_id', auth()->id())
            ->latest('enrolled_at')
            ->get();
    }

    public function canOpenEvent(Enrollment $enrollment): bool
    {
        /** @var Event $event */
        $event = $enrollment->event;

        return $event->status->isViewableByParticipant();
    }

    public function eventUrl(Enrollment $enrollment): string
    {
        return EventPage::getUrl(['record' => $enrollment->event_id]);
    }

    public function render(): View
    {
        return view('panel-app::livewire.events.my-events-list');
    }
}
