<?php

namespace App\Observers;

use App\Actions\Gamefication\GiveXP;
use App\Exceptions\UserException;
use App\Models\User\Message;
use Illuminate\Support\Facades\Log;

class MessageObserver
{
    private GiveXP $giveXP;

    public function __construct(GiveXP $giveXP)
    {
        $this->giveXP = $giveXP;
    }

    public function creating(Message $message)
    {
        $message->season_id = config('he4rt.season');

        $points = config('gambling.xp.message');

        try {
            $message->obtained_experience = $this->giveXP->handle($message->user->getKey(), $points);
        } catch (UserException $e) {
            Log::error($e->getMessage(), [
                'user' => $message->user(),
                'message' => $message,
                'should_receive_xp' => $points
            ]);
        }
    }
}
