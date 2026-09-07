<?php

declare(strict_types=1);

namespace He4rt\Live\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/** Nova mensagem no chat da live. */
final class ChatMessageSent implements ShouldBroadcastNow
{
    use Dispatchable;

    /** @param array<string, string> $message */
    public function __construct(
        public string $liveId,
        public array $message,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('live.'.$this->liveId);
    }

    public function broadcastAs(): string
    {
        return 'ChatMessageSent';
    }
}
