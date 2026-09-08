<?php

declare(strict_types=1);

namespace He4rt\Identity\ExternalIdentity\Events;

use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched when an external identity is connected (or reconnected) to a user.
 * Consumers reconcile records that were waiting for this identity to appear.
 */
final class ExternalIdentityConnected
{
    use Dispatchable;

    public function __construct(
        public ExternalIdentity $identity,
    ) {}
}
