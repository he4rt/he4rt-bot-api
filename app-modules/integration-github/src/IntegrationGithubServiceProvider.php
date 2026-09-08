<?php

declare(strict_types=1);

namespace He4rt\IntegrationGithub;

use He4rt\Identity\ExternalIdentity\Events\ExternalIdentityConnected;
use He4rt\IntegrationGithub\Console\BackfillGithubCommand;
use He4rt\IntegrationGithub\Console\ProjectGithubContributionsCommand;
use He4rt\IntegrationGithub\Contributions\Listeners\QueueContributionAdoption;
use He4rt\IntegrationGithub\Contributions\TrackGithubContribution;
use He4rt\IntegrationGithub\Events\GithubContributionChanged;
use He4rt\IntegrationGithub\Events\GithubContributionRecorded;
use He4rt\IntegrationGithub\Models\GithubContribution;
use He4rt\IntegrationGithub\Retrospective\GithubSource;
use He4rt\IntegrationGithub\Transport\GitHubApiConnector;
use He4rt\IntegrationGithub\Transport\GitHubOAuthConnector;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class IntegrationGithubServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/integration-github.php', 'integration-github');

        $this->app->singleton(GitHubOAuthConnector::class, static function (): GitHubOAuthConnector {
            $clientId = config('services.github.client_id');
            $clientSecret = config('services.github.client_secret');

            throw_if($clientId === null || $clientSecret === null, RuntimeException::class, 'GitHub OAuth credentials are not configured (services.github.client_id / client_secret).');

            return new GitHubOAuthConnector(
                clientId: $clientId,
                clientSecret: $clientSecret,
            );
        });

        $this->app->singleton(GitHubApiConnector::class, fn () => new GitHubApiConnector());

        // Fonte da retrospectiva, descoberta pelo portal via tagged services.
        $this->app->tag([GithubSource::class], 'retrospective.source');
    }

    public function boot(): void
    {
        Relation::morphMap([
            'github_contribution' => GithubContribution::class,
        ]);

        Event::listen(GithubContributionRecorded::class, [TrackGithubContribution::class, 'onRecorded']);
        Event::listen(GithubContributionChanged::class, [TrackGithubContribution::class, 'onChanged']);
        Event::listen(ExternalIdentityConnected::class, [QueueContributionAdoption::class, 'handle']);

        if ($this->app->runningInConsole()) {
            $this->commands([
                BackfillGithubCommand::class,
                ProjectGithubContributionsCommand::class,
            ]);
        }
    }
}
