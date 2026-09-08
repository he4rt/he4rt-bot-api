<?php

declare(strict_types=1);

use He4rt\Activity\Tracking\Models\Interaction;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Events\ExternalIdentityConnected;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use He4rt\IntegrationGithub\Contributions\Jobs\AdoptGithubContributions;
use He4rt\IntegrationGithub\Contributions\TrackGithubContribution;
use He4rt\IntegrationGithub\Enums\ContributionType;
use He4rt\IntegrationGithub\Models\GithubContribution;
use Illuminate\Support\Facades\Queue;

function identityFor(IdentityProvider $provider, int $accountId = 31_713_982, string $username = 'fulano'): ExternalIdentity
{
    return ExternalIdentity::factory()->create([
        'model_type' => (new User)->getMorphClass(),
        'model_id' => User::factory(),
        'provider' => $provider,
        'external_account_id' => (string) $accountId,
        'connected_at' => now(),
        'disconnected_at' => null,
        'metadata' => ['username' => $username],
    ]);
}

test('conectar o GitHub enfileira a adoção', function (): void {
    Queue::fake();

    $identity = identityFor(IdentityProvider::GitHub);

    event(new ExternalIdentityConnected($identity));

    Queue::assertPushed(AdoptGithubContributions::class);
});

test('conectar outro provider não enfileira nada', function (): void {
    Queue::fake();

    event(new ExternalIdentityConnected(identityFor(IdentityProvider::Twitch)));

    Queue::assertNotPushed(AdoptGithubContributions::class);
});

test('a adoção registra as contribuições que já estavam no lake', function (): void {
    $identity = identityFor(IdentityProvider::GitHub);

    GithubContribution::factory()->count(4)->sequence(
        fn ($sequence) => [
            'type' => ContributionType::Commit,
            'external_ref' => 'commit:sha'.$sequence->index,
            'actor_id' => null,
            'actor_login' => 'Fulano',
        ],
    )->create();

    GithubContribution::factory()->create([
        'type' => ContributionType::Issue,
        'external_ref' => 'issue:9',
        'actor_id' => null,
        'actor_login' => 'outra-pessoa',
    ]);

    new AdoptGithubContributions($identity->id)->handle(resolve(TrackGithubContribution::class));

    expect(Interaction::query()->count())->toBe(4)
        ->and(Interaction::query()->pluck('user_id')->unique()->all())->toBe([$identity->model_id]);
});

test('rodar a adoção de novo não duplica', function (): void {
    $identity = identityFor(IdentityProvider::GitHub);

    GithubContribution::factory()->create([
        'type' => ContributionType::Review,
        'external_ref' => 'review:1',
        'actor_id' => 31_713_982,
    ]);

    $producer = resolve(TrackGithubContribution::class);

    new AdoptGithubContributions($identity->id)->handle($producer);
    new AdoptGithubContributions($identity->id)->handle($producer);

    expect(Interaction::query()->count())->toBe(1);
});
