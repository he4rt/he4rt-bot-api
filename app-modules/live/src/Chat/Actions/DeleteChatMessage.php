<?php

declare(strict_types=1);

namespace He4rt\Live\Chat\Actions;

use He4rt\Activity\Message\Models\Message;
use He4rt\Activity\Moderation\Enums\ModerationType;
use He4rt\Activity\Moderation\Models\ModerationEvent;
use He4rt\Identity\User\Models\User;
use He4rt\Live\Events\ChatMessageDeleted;
use Illuminate\Support\Facades\DB;

/** Remove uma mensagem do chat com trilha de moderação e aviso em tempo real. */
final readonly class DeleteChatMessage
{
    public function __construct(private ResolveChatIdentity $resolveChatIdentity) {}

    public function execute(Message $message, User $moderator): void
    {
        $liveId = (string) $message->channel_id;
        $messageId = $message->id;
        $moderatorIdentity = $this->resolveChatIdentity->execute($moderator);

        DB::transaction(function () use ($message, $moderatorIdentity, $liveId): void {
            ModerationEvent::query()->create([
                'external_identity_id' => $message->external_identity_id,
                'moderator_identity_id' => $moderatorIdentity->id,
                'type' => ModerationType::MessageDeleted,
                'metadata' => ['content' => $message->content, 'live_id' => $liveId, 'message_id' => $message->id],
                'occurred_at' => now(),
            ]);

            $message->delete();
        });

        rescue(fn () => event(new ChatMessageDeleted($liveId, $messageId)), report: true);
    }
}
