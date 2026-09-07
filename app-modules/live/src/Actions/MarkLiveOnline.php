<?php

declare(strict_types=1);

namespace He4rt\Live\Actions;

use He4rt\Live\Enums\LiveStatus;
use He4rt\Live\Events\LiveStarted;
use He4rt\Live\Models\Live;

/** Reage ao sinal online do mediamtx: primeira vez marca início; reconexões só re-avisam o front. */
final readonly class MarkLiveOnline
{
    public function execute(Live $live): Live
    {
        $live->update([
            'status' => LiveStatus::OnAir,
            'started_at' => $live->started_at ?? now(),
        ]);

        rescue(fn () => event(new LiveStarted($live->id)), report: true);

        return $live->refresh();
    }
}
