<?php

declare(strict_types=1);

namespace He4rt\Events\Closure\Jobs;

use He4rt\Events\Closure\Actions\CloseEventAction;
use He4rt\Events\Event\Models\Event;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\Attributes\UniqueFor;
use Illuminate\Support\Facades\Log;
use Throwable;

#[Backoff([1, 5, 10])]
#[Tries(tries: 4)]
#[UniqueFor(uniqueFor: 1_800)]
final class ProcessEventClosureJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $eventId) {}

    public function uniqueId(): string
    {
        return $this->eventId;
    }

    public function handle(CloseEventAction $action): void
    {
        $event = Event::query()->find($this->eventId);

        if ($event === null) {
            return;
        }

        $action->handle($event);
    }

    public function failed(Throwable $e): void
    {
        Log::error('Event closure failed', [
            'event_id' => $this->eventId,
            'error' => $e->getMessage(),
        ]);
    }
}
