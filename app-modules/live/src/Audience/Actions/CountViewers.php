<?php

declare(strict_types=1);

namespace He4rt\Live\Audience\Actions;

use He4rt\Live\Contracts\ViewerPresenceContract;
use He4rt\Live\Models\Live;

/** Conta os espectadores ativos da live. */
final readonly class CountViewers
{
    public function __construct(private ViewerPresenceContract $presence) {}

    public function execute(Live $live): int
    {
        return $this->presence->countActive($live->id);
    }
}
