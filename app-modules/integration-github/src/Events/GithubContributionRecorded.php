<?php

declare(strict_types=1);

namespace He4rt\IntegrationGithub\Events;

use He4rt\IntegrationGithub\Models\GithubContribution;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Seam de criação: emitida quando uma contribuição inédita é registrada, tanto pelo
 * webhook quanto pelo backfill. O Tracking a escuta para resolver a identidade
 * conectada do contribuidor e registrar a contribuição canônica.
 */
final readonly class GithubContributionRecorded
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public GithubContribution $contribution,
    ) {}
}
