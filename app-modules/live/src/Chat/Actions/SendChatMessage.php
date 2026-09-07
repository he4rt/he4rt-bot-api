<?php

declare(strict_types=1);

namespace He4rt\Live\Chat\Actions;

use He4rt\Activity\Message\Enums\MessageKind;
use He4rt\Activity\Message\Enums\MessageSourceKind;
use He4rt\Activity\Message\Models\Message;
use He4rt\Identity\User\Enums\UserSituation;
use He4rt\Identity\User\Models\User;
use He4rt\Live\Chat\DTOs\ChatMessageData;
use He4rt\Live\Chat\Exceptions\ChatMessageRejected;
use He4rt\Live\Enums\LiveStatus;
use He4rt\Live\Events\ChatMessageSent;
use He4rt\Live\Models\Live;
use Illuminate\Support\Facades\RateLimiter;

/** Envia uma mensagem ao chat da live, persistindo no pipeline de atividade. */
final readonly class SendChatMessage
{
    private const int MAX_MESSAGES = 5;

    private const int DECAY_SECONDS = 10;

    public function __construct(private ResolveChatIdentity $resolveChatIdentity) {}

    public function execute(User $user, Live $live, string $content): ChatMessageData
    {
        if ($user->situation !== UserSituation::Active) {
            throw ChatMessageRejected::userBlocked();
        }

        if ($live->status === LiveStatus::Ended) {
            throw ChatMessageRejected::liveNotAcceptingMessages();
        }

        $rateLimitKey = 'live-chat:'.$user->id;

        if (RateLimiter::tooManyAttempts($rateLimitKey, self::MAX_MESSAGES)) {
            throw ChatMessageRejected::rateLimited(RateLimiter::availableIn($rateLimitKey));
        }

        RateLimiter::hit($rateLimitKey, self::DECAY_SECONDS);

        $identity = $this->resolveChatIdentity->execute($user);

        $message = Message::query()->create([
            'external_identity_id' => $identity->id,
            'channel_id' => $live->id,
            'content' => $content,
            'obtained_experience' => 0,
            'kind' => MessageKind::Default,
            'source_kind' => MessageSourceKind::User,
            'sent_at' => now(),
        ]);

        $data = ChatMessageData::fromMessage($message, $user);

        rescue(fn () => event(new ChatMessageSent($live->id, $data->toArray())), report: true);

        return $data;
    }
}
