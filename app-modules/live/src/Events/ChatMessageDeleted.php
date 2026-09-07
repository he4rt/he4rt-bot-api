<?php

declare(strict_types=1);

namespace He4rt\Live\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/** Mensagem removida do chat da live. */
final class ChatMessageDeleted implements ShouldBroadcastNow
{
    use Dispatchable;

    public function __construct(
        public string $liveId,
        public string $messageId,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('live.'.$this->liveId);
    }

    public function broadcastAs(): string
    {
        return 'ChatMessageDeleted';
    }
}
