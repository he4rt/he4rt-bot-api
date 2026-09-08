<?php

declare(strict_types=1);

namespace He4rt\IntegrationGithub\Contributions;

use He4rt\IntegrationGithub\Contributions\DTOs\NewContributionDTO;
use He4rt\IntegrationGithub\Enums\ContributionType;
use He4rt\IntegrationGithub\Events\GithubContributionChanged;
use He4rt\IntegrationGithub\Events\GithubContributionRecorded;
use He4rt\IntegrationGithub\Models\GithubContribution;

/**
 * Idempotent writer for contributions, shared by backfill and webhook ingestion.
 * Convergence is guaranteed by the unique (repo, type, external_ref) key.
 *
 * Ambos os caminhos emitem: um registro que entra pelo backfill precisa alcançar
 * o Tracking igual ao que entra pelo webhook, senão recuperar webhook perdido
 * deixa o hub cego para tudo que o backfill trouxer.
 */
final class RecordContribution
{
    public function execute(NewContributionDTO $contribution, bool $emit = false): GithubContribution
    {
        $existing = GithubContribution::query()
            ->where('repo', $contribution->repo)
            ->where('type', $contribution->type)
            ->where('external_ref', $contribution->externalRef)
            ->first();

        $wasMerged = $this->isMerged($existing?->metadata);

        $recorded = GithubContribution::query()->updateOrCreate(
            [
                'repo' => $contribution->repo,
                'type' => $contribution->type,
                'external_ref' => $contribution->externalRef,
            ],
            [
                'actor_login' => $contribution->actorLogin,
                'actor_id' => $contribution->actorId,
                'target_ref' => $contribution->targetRef,
                'occurred_at' => $contribution->occurredAt,
                'metadata' => $contribution->metadata,
            ],
        );

        if (!$emit) {
            return $recorded;
        }

        if ($recorded->wasRecentlyCreated) {
            event(new GithubContributionRecorded($recorded));

            return $recorded;
        }

        if ($this->justMerged($recorded, $wasMerged)) {
            event(new GithubContributionChanged($recorded));
        }

        return $recorded;
    }

    /**
     * Única transição que vira fato novo. Edição de título, synchronize e mudança
     * de state seguem mudando a linha em silêncio.
     */
    private function justMerged(GithubContribution $recorded, bool $wasMerged): bool
    {
        return $recorded->type === ContributionType::Pr
            && !$wasMerged
            && $this->isMerged($recorded->metadata);
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    private function isMerged(?array $metadata): bool
    {
        return ($metadata['merged'] ?? false) === true;
    }
}
