<?php

namespace Tests\Providers;

use App\Models\User\User;
use Str;

class FeedbackProvider
{
    public static function payload(int $sender = null, int $target = null): array
    {
        return [
            'sender_id' => $sender ?: null,
            'target_id' => $target ?: null,
            'message'   =>
                'So... I heard u saying some weird shit in the call before.
                It would be better if u keep that kind of stuff to yourself.',
            'type' => 'good',
        ];
    }

    public static function invalidPayload(): array
    {
        return [
            'message' => Str::random(4001),
        ];
    }
}
