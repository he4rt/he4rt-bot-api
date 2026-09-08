<?php

declare(strict_types=1);

namespace He4rt\Activity\Tracking\Actions;

use He4rt\Activity\Tracking\Models\Interaction;

final readonly class UnhideInteraction
{
    public function handle(Interaction $interaction): Interaction
    {
        $interaction->update([
            'hidden_at' => null,
            'hidden_by' => null,
        ]);

        return $interaction->fresh();
    }
}
