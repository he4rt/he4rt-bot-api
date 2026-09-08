<?php

declare(strict_types=1);

namespace He4rt\IntegrationGithub\Events;

use He4rt\IntegrationGithub\Models\GithubContribution;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Seam de transição: emitida quando uma contribuição já registrada muda de estado
 * de um jeito que cria um fato novo. Hoje a única transição é o merge de um PR —
 * a linha do lake é a mesma, mas passa a representar também "PR incorporado".
 */
final readonly class GithubContributionChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public GithubContribution $contribution,
    ) {}
}
