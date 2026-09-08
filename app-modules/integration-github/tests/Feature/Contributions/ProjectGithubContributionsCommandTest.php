<?php

declare(strict_types=1);

use He4rt\Activity\Tracking\Models\Interaction;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use He4rt\IntegrationGithub\Enums\ContributionType;
use He4rt\IntegrationGithub\Models\GithubContribution;

function connectedContributor(int $accountId = 31_713_982): ExternalIdentity
{
    return ExternalIdentity::factory()->create([
        'model_type' => (new User)->getMorphClass(),
        'model_id' => User::factory(),
        'provider' => IdentityProvider::GitHub,
        'external_account_id' => (string) $accountId,
        'connected_at' => now(),
        'disconnected_at' => null,
        'metadata' => ['username' => 'fulano'],
    ]);
}

test('projeta só o que tem identidade conectada', function (): void {
    connectedContributor();

    GithubContribution::factory()->count(2)->sequence(
        fn ($sequence) => [
            'type' => ContributionType::Review,
            'external_ref' => 'review:'.$sequence->index,
            'actor_id' => 31_713_982,
        ],
    )->create();

    GithubContribution::factory()->count(3)->sequence(
        fn ($sequence) => [
            'type' => ContributionType::Commit,
            'external_ref' => 'commit:x'.$sequence->index,
            'actor_id' => null,
            'actor_login' => 'sem-conta',
        ],
    )->create();

    $this->artisan('github:project-contributions')->assertSuccessful();

    expect(Interaction::query()->count())->toBe(2)
        ->and(GithubContribution::query()->count())->toBe(5);
});

test('rodar duas vezes não duplica', function (): void {
    connectedContributor();

    GithubContribution::factory()->create([
        'type' => ContributionType::Issue,
        'external_ref' => 'issue:1',
        'actor_id' => 31_713_982,
    ]);

    $this->artisan('github:project-contributions')->assertSuccessful();
    $this->artisan('github:project-contributions')->assertSuccessful();

    expect(Interaction::query()->count())->toBe(1);
});

test('dry-run não escreve', function (): void {
    connectedContributor();

    GithubContribution::factory()->create([
        'type' => ContributionType::Issue,
        'external_ref' => 'issue:2',
        'actor_id' => 31_713_982,
    ]);

    $this->artisan('github:project-contributions', ['--dry-run' => true])->assertSuccessful();

    expect(Interaction::query()->count())->toBe(0);
});
