<?php

declare(strict_types=1);

namespace He4rt\Activity\Tracking\Listeners;

use He4rt\Activity\Tracking\Models\Interaction;
use He4rt\Identity\Auth\Events\AccountsMerged;

/**
 * O merge repõe external_identities.model_id em massa, o que deixaria o user_id
 * denormalizado das interações apontando para a conta que deixou de existir.
 */
final class ReassignInteractionOwnership
{
    public function handle(AccountsMerged $event): void
    {
        Interaction::query()
            ->where('user_id', $event->mergedId)
            ->update(['user_id' => $event->survivorId]);
    }
}
