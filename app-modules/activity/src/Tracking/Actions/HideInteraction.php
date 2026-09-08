<?php

declare(strict_types=1);

namespace He4rt\Activity\Tracking\Actions;

use He4rt\Activity\Tracking\Models\Interaction;
use He4rt\Identity\User\Models\User;

final readonly class HideInteraction
{
    public function handle(Interaction $interaction, User $actor): Interaction
    {
        $interaction->update([
            'hidden_at' => now(),
            'hidden_by' => $actor->id,
        ]);

        return $interaction->fresh();
    }
}
