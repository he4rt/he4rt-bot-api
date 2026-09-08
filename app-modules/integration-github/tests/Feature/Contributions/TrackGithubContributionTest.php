<?php

declare(strict_types=1);

use He4rt\Activity\Tracking\Enums\ActivityType;
use He4rt\Activity\Tracking\Enums\AttributionMethod;
use He4rt\Activity\Tracking\Models\Interaction;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use He4rt\IntegrationGithub\Contributions\TrackGithubContribution;
use He4rt\IntegrationGithub\Enums\ContributionType;
use He4rt\IntegrationGithub\Events\GithubContributionChanged;
use He4rt\IntegrationGithub\Events\GithubContributionRecorded;
use He4rt\IntegrationGithub\Models\GithubContribution;

function connectedGithub(int $accountId = 31_713_982, string $username = 'fulano'): ExternalIdentity
{
    return ExternalIdentity::factory()->create([
        'model_type' => (new User)->getMorphClass(),
        'model_id' => User::factory(),
        'provider' => IdentityProvider::GitHub,
        'external_account_id' => (string) $accountId,
        'connected_at' => now(),
        'disconnected_at' => null,
        'metadata' => ['username' => $username],
    ]);
}

test('PR aberto vira pr_opened com ref namespaced pelo repo', function (): void {
    $identity = connectedGithub();

    $contribution = GithubContribution::factory()->create([
        'repo' => 'he4rt/heartdevs.com',
        'type' => ContributionType::Pr,
        'external_ref' => 'pr:474',
        'actor_id' => 31_713_982,
    ]);

    resolve(TrackGithubContribution::class)->onRecorded(new GithubContributionRecorded($contribution));

    $interaction = Interaction::query()->firstOrFail();

    expect($interaction->type)->toBe(ActivityType::PrOpened)
        ->and($interaction->external_ref)->toBe('github:pr_opened:he4rt/heartdevs.com:474')
        ->and($interaction->external_identity_id)->toBe($identity->id)
        ->and($interaction->source_type)->toBe('github_contribution')
        ->and($interaction->source_id)->toBe($contribution->id)
        ->and($interaction->attributed_by)->toBe(AttributionMethod::ExternalId);
});

test('transição de merge vira pr_merged com a data do merge', function (): void {
    connectedGithub();

    $contribution = GithubContribution::factory()
        ->merged('2026-08-20T12:00:00Z')
        ->create([
            'repo' => 'he4rt/heartdevs.com',
            'external_ref' => 'pr:474',
            'actor_id' => 31_713_982,
        ]);

    resolve(TrackGithubContribution::class)->onChanged(new GithubContributionChanged($contribution));

    $interaction = Interaction::query()->firstOrFail();

    expect($interaction->type)->toBe(ActivityType::PrMerged)
        ->and($interaction->external_ref)->toBe('github:pr_merged:he4rt/heartdevs.com:474')
        ->and($interaction->occurred_at->toIso8601String())->toStartWith('2026-08-20T12:00:00');
});

test('adotar um PR já mergeado registra abertura e merge', function (): void {
    connectedGithub();

    $contribution = GithubContribution::factory()
        ->merged('2026-08-20T12:00:00Z')
        ->create([
            'repo' => 'he4rt/heartdevs.com',
            'external_ref' => 'pr:474',
            'actor_id' => 31_713_982,
        ]);

    resolve(TrackGithubContribution::class)->adopt($contribution);

    expect(Interaction::query()->pluck('type')->map->value->sort()->values()->all())
        ->toBe(['pr_merged', 'pr_opened']);
});

test('cada tipo do lake mapeia para o tipo canônico', function (ContributionType $lake, ActivityType $expected): void {
    connectedGithub();

    $contribution = GithubContribution::factory()->create([
        'type' => $lake,
        'external_ref' => $lake->ref(99),
        'actor_id' => 31_713_982,
    ]);

    resolve(TrackGithubContribution::class)->onRecorded(new GithubContributionRecorded($contribution));

    expect(Interaction::query()->firstOrFail()->type)->toBe($expected);
})->with([
    [ContributionType::Review, ActivityType::Review],
    [ContributionType::ReviewComment, ActivityType::ReviewComment],
    [ContributionType::Comment, ActivityType::Comment],
    [ContributionType::Commit, ActivityType::Commit],
    [ContributionType::Issue, ActivityType::Issue],
]);

test('contribuidor sem conta conectada não gera interação e permanece no lake', function (): void {
    $contribution = GithubContribution::factory()->create([
        'actor_id' => 777,
        'actor_login' => 'ninguem',
    ]);

    resolve(TrackGithubContribution::class)->onRecorded(new GithubContributionRecorded($contribution));

    expect(Interaction::query()->count())->toBe(0)
        ->and(GithubContribution::query()->whereKey($contribution->id)->exists())->toBeTrue();
});
