<?php

namespace Tests\Providers;

use App\Models\User\User;
use Str;

class FeedbackProvider
{
    public static function validPayload(?User $sender = null, ?User $target = null): array
    {
        return [
            'sender_id' => $sender ? $sender->discord_id : null,
            'target_id' => $target ? $target->discord_id : null,
            'message'   =>
                'So... I heard u saying some weird shit in the call before.
                It would be better if u keep that kind of stuff to yourself.',
            'type' => 'good'
        ];
    }

    public static function invalidPayload(): array
    {
        return [
            'message' => Str::random(4001),
        ];
    }
}
