<?php

declare(strict_types=1);

use He4rt\Activity\Tracking\Enums\AttributionMethod;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use He4rt\IntegrationGithub\Contributions\ResolveContributorIdentity;
use He4rt\IntegrationGithub\Enums\ContributionType;
use He4rt\IntegrationGithub\Models\GithubContribution;

function githubIdentity(string $accountId, string $username, array $overrides = []): ExternalIdentity
{
    return ExternalIdentity::factory()->create([
        ...[
            'model_type' => (new User)->getMorphClass(),
            'model_id' => User::factory(),
            'provider' => IdentityProvider::GitHub,
            'external_account_id' => $accountId,
            'connected_at' => now(),
            'disconnected_at' => null,
            'metadata' => ['username' => $username],
        ],
        ...$overrides,
    ]);
}

test('casa exatamente pelo actor_id', function (): void {
    $identity = githubIdentity('31713982', 'tecrodrigocastro');

    $contribution = GithubContribution::factory()->create([
        'actor_id' => 31_713_982,
        'actor_login' => 'outro-login-qualquer',
    ]);

    $resolved = resolve(ResolveContributorIdentity::class)->handle($contribution);

    expect($resolved['identity']->id)->toBe($identity->id)
        ->and($resolved['attributed_by'])->toBe(AttributionMethod::ExternalId);
});

test('commit sem actor_id casa pelo login, ignorando caixa', function (): void {
    $identity = githubIdentity('999111', 'fulano');

    $contribution = GithubContribution::factory()->create([
        'type' => ContributionType::Commit,
        'actor_id' => null,
        'actor_login' => 'Fulano',
    ]);

    $resolved = resolve(ResolveContributorIdentity::class)->handle($contribution);

    expect($resolved['identity']->id)->toBe($identity->id)
        ->and($resolved['attributed_by'])->toBe(AttributionMethod::Handle);
});

test('login ambíguo é descartado em vez de adivinhado', function (): void {
    githubIdentity('111', 'colidido');
    githubIdentity('222', 'colidido');

    $contribution = GithubContribution::factory()->create([
        'actor_id' => null,
        'actor_login' => 'colidido',
    ]);

    expect(resolve(ResolveContributorIdentity::class)->handle($contribution))->toBeNull();
});

test('ator sem identidade conectada é descartado', function (): void {
    $contribution = GithubContribution::factory()->create([
        'actor_id' => 4_242,
        'actor_login' => 'desconhecido',
    ]);

    expect(resolve(ResolveContributorIdentity::class)->handle($contribution))->toBeNull();
});

test('identidade desconectada não resolve', function (): void {
    githubIdentity('555', 'saiu', ['disconnected_at' => now()]);

    $contribution = GithubContribution::factory()->create([
        'actor_id' => 555,
        'actor_login' => 'saiu',
    ]);

    expect(resolve(ResolveContributorIdentity::class)->handle($contribution))->toBeNull();
});
