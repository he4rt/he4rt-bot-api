<?php

declare(strict_types=1);

namespace He4rt\Live\Audience\Actions;

use He4rt\Live\Contracts\ViewerPresenceContract;
use He4rt\Live\Models\Live;

/** Registra o "estou assistindo" de uma aba e devolve a audiência atual. */
final readonly class RecordViewerHeartbeat
{
    public function __construct(private ViewerPresenceContract $presence) {}

    public function execute(Live $live, string $visitorId): int
    {
        $this->presence->touch($live->id, $visitorId);

        $viewers = $this->presence->countActive($live->id);

        if ($viewers > $live->peak_viewers) {
            $live->update(['peak_viewers' => $viewers]);
        }

        return $viewers;
    }
}
