<?php

namespace App\Observers;

use App\Actions\Gamefication\ExperienceLeveling;
use App\Models\User\Message;

class MessageObserver
{
    private ExperienceLeveling $experienceAction;

    public function __construct(ExperienceLeveling $experienceAction)
    {
        $this->experienceAction = $experienceAction;
    }

    public function creating(Message $message)
    {
        $message->season_id = config('he4rt.season');
        $message->obtained_experience = $this->experienceAction->handle(
            $message->user->getKey()
        );
    }
}

