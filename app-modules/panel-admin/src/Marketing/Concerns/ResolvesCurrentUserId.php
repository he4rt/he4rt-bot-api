<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Marketing\Concerns;

/**
 * The `id` of a User is a UUID, but `auth()->id()` stays typed as
 * `int|string|null` because the contract still allows auto-increment keys.
 * Narrowing it here is what keeps the marketing DTOs honest.
 */
trait ResolvesCurrentUserId
{
    protected function currentUserId(): ?string
    {
        $id = auth()->id();

        return is_string($id) ? $id : null;
    }
}
