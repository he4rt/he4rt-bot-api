<?php

declare(strict_types=1);

namespace He4rt\IntegrationGithub\Contributions;

use DateTimeImmutable;
use He4rt\Activity\Tracking\Actions\TrackActivity;
use He4rt\Activity\Tracking\DTOs\TrackActivityDTO;
use He4rt\Activity\Tracking\Enums\ActivityType;
use He4rt\Activity\Tracking\Models\Interaction;
use He4rt\IntegrationGithub\Enums\ContributionType;
use He4rt\IntegrationGithub\Events\GithubContributionChanged;
use He4rt\IntegrationGithub\Events\GithubContributionRecorded;
use He4rt\IntegrationGithub\Models\GithubContribution;

/**
 * Projeta uma contribuição do lake na contribuição canônica do Tracking.
 *
 * Mora aqui, e não em activity, porque domain não importa integration — é o
 * inverso do TrackContentContribution, cujo contents é domain.
 */
final readonly class TrackGithubContribution
{
    public function __construct(
        private ResolveContributorIdentity $resolveIdentity,
        private TrackActivity $trackActivity,
    ) {}

    public function onRecorded(GithubContributionRecorded $event): void
    {
        $this->project($event->contribution, $this->typeFor($event->contribution));
    }

    public function onChanged(GithubContributionChanged $event): void
    {
        $this->project($event->contribution, ActivityType::PrMerged);
    }

    public function adopt(GithubContribution $contribution): ?Interaction
    {
        $tracked = $this->project($contribution, $this->typeFor($contribution));

        // Um PR já mergeado no lake carrega dois fatos: a abertura e o merge.
        if ($this->isMerged($contribution)) {
            $this->project($contribution, ActivityType::PrMerged);
        }

        return $tracked;
    }

    private function project(GithubContribution $contribution, ActivityType $type): ?Interaction
    {
        $resolved = $this->resolveIdentity->handle($contribution);

        if ($resolved === null) {
            return null;
        }

        return $this->trackActivity->handle(new TrackActivityDTO(
            externalIdentityId: $resolved['identity']->id,
            type: $type,
            attributedBy: $resolved['attributed_by'],
            occurredAt: $this->occurredAt($contribution, $type),
            externalRef: $this->externalRef($contribution, $type),
            sourceType: 'github_contribution',
            sourceId: $contribution->id,
        ));
    }

    private function typeFor(GithubContribution $contribution): ActivityType
    {
        return match ($contribution->type) {
            ContributionType::Pr => ActivityType::PrOpened,
            ContributionType::Review => ActivityType::Review,
            ContributionType::ReviewComment => ActivityType::ReviewComment,
            ContributionType::Comment => ActivityType::Comment,
            ContributionType::Commit => ActivityType::Commit,
            ContributionType::Issue => ActivityType::Issue,
        };
    }

    private function externalRef(GithubContribution $contribution, ActivityType $type): string
    {
        return sprintf(
            'github:%s:%s:%s',
            $type->value,
            $contribution->repo,
            $this->localRef($contribution),
        );
    }

    /**
     * O ref do lake vem namespaced ("pr:474"); aqui o tipo já vive noutro segmento.
     */
    private function localRef(GithubContribution $contribution): string
    {
        $parts = explode(':', $contribution->external_ref, 2);

        return $parts[1] ?? $contribution->external_ref;
    }

    private function occurredAt(GithubContribution $contribution, ActivityType $type): DateTimeImmutable
    {
        $mergedAt = $contribution->metadata['merged_at'] ?? null;

        if ($type === ActivityType::PrMerged && is_string($mergedAt)) {
            return new DateTimeImmutable($mergedAt);
        }

        return $contribution->occurred_at->toDateTimeImmutable();
    }

    private function isMerged(GithubContribution $contribution): bool
    {
        return $contribution->type === ContributionType::Pr
            && ($contribution->metadata['merged'] ?? false) === true;
    }
}
