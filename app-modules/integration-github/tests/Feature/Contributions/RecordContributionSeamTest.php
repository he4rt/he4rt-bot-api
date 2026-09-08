<?php

declare(strict_types=1);

use He4rt\IntegrationGithub\Contributions\DTOs\NewContributionDTO;
use He4rt\IntegrationGithub\Contributions\RecordContribution;
use He4rt\IntegrationGithub\Enums\ContributionType;
use He4rt\IntegrationGithub\Events\GithubContributionChanged;
use He4rt\IntegrationGithub\Events\GithubContributionRecorded;
use Illuminate\Support\Facades\Event;

function prContribution(bool $merged, string $title = 'Um PR'): NewContributionDTO
{
    return new NewContributionDTO(
        repo: 'he4rt/heartdevs.com',
        type: ContributionType::Pr,
        externalRef: 'pr:474',
        actorLogin: 'fulano',
        actorId: 31_713_982,
        occurredAt: '2026-08-01T10:00:00Z',
        targetRef: null,
        metadata: [
            'title' => $title,
            'state' => $merged ? 'closed' : 'open',
            'merged' => $merged,
            'merged_at' => $merged ? '2026-08-20T12:00:00Z' : null,
            'is_bot' => false,
        ],
    );
}

test('PR aberto emite criação', function (): void {
    Event::fake([GithubContributionRecorded::class, GithubContributionChanged::class]);

    resolve(RecordContribution::class)->execute(prContribution(merged: false), emit: true);

    Event::assertDispatched(GithubContributionRecorded::class);
    Event::assertNotDispatched(GithubContributionChanged::class);
});

test('PR mergeado emite transição, não criação', function (): void {
    resolve(RecordContribution::class)->execute(prContribution(merged: false), emit: true);

    Event::fake([GithubContributionRecorded::class, GithubContributionChanged::class]);

    resolve(RecordContribution::class)->execute(prContribution(merged: true), emit: true);

    Event::assertDispatched(GithubContributionChanged::class);
    Event::assertNotDispatched(GithubContributionRecorded::class);
});

test('push num PR aberto não emite nada', function (): void {
    resolve(RecordContribution::class)->execute(prContribution(merged: false), emit: true);

    Event::fake([GithubContributionRecorded::class, GithubContributionChanged::class]);

    // synchronize: mexe no título e nos diffs, sem mudar o estado do merge.
    resolve(RecordContribution::class)->execute(prContribution(merged: false, title: 'Outro título'), emit: true);

    Event::assertNothingDispatched();
});

test('merge reprocessado não emite de novo', function (): void {
    resolve(RecordContribution::class)->execute(prContribution(merged: false), emit: true);
    resolve(RecordContribution::class)->execute(prContribution(merged: true), emit: true);

    Event::fake([GithubContributionChanged::class]);

    resolve(RecordContribution::class)->execute(prContribution(merged: true), emit: true);

    Event::assertNotDispatched(GithubContributionChanged::class);
});

test('sem emit, nenhum caminho avisa', function (): void {
    Event::fake([GithubContributionRecorded::class, GithubContributionChanged::class]);

    resolve(RecordContribution::class)->execute(prContribution(merged: false));

    Event::assertNothingDispatched();
});
