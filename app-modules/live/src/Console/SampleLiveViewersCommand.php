<?php

declare(strict_types=1);

namespace He4rt\Live\Console;

use He4rt\Live\Audience\Actions\CountViewers;
use He4rt\Live\Enums\LiveStatus;
use He4rt\Live\Models\Live;
use He4rt\Live\Models\LiveViewerSample;
use Illuminate\Console\Command;

/** Grava a audiência da live no ar para a série temporal do admin. */
final class SampleLiveViewersCommand extends Command
{
    protected $signature = 'live:sample-viewers';

    protected $description = 'Grava uma amostra de audiência da live no ar';

    public function handle(CountViewers $countViewers): int
    {
        $live = Live::query()->where('status', LiveStatus::OnAir)->first();

        if ($live === null) {
            return self::SUCCESS;
        }

        LiveViewerSample::query()->create([
            'live_id' => $live->id,
            'viewers' => $countViewers->execute($live),
            'sampled_at' => now(),
        ]);

        return self::SUCCESS;
    }
}
