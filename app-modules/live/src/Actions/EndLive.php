<?php

declare(strict_types=1);

namespace He4rt\Live\Actions;

use He4rt\Live\Enums\LiveStatus;
use He4rt\Live\Events\LiveEnded;
use He4rt\Live\Models\Live;

/** Encerra a live: a stream key deixa de valer e o front é avisado. */
final readonly class EndLive
{
    public function execute(Live $live): Live
    {
        $live->update(['status' => LiveStatus::Ended, 'ended_at' => now()]);

        rescue(fn () => event(new LiveEnded($live->id)), report: true);

        return $live->refresh();
    }
}
