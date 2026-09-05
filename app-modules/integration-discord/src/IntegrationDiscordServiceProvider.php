<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord;

use He4rt\Community\Retrospective\Contracts\MembershipDates;
use He4rt\IntegrationDiscord\ETL\Console\BackfillVoiceLogsCommand;
use He4rt\IntegrationDiscord\ETL\Console\ImportDiscordMessagesCommand;
use He4rt\IntegrationDiscord\ETL\Console\ImportDiscordProfilesCommand;
use He4rt\IntegrationDiscord\ETL\Console\MergeDuplicateDiscordProfilesCommand;
use He4rt\IntegrationDiscord\Models\DiscordEventLog;
use He4rt\IntegrationDiscord\Retrospective\DiscordMembershipDates;
use He4rt\IntegrationDiscord\Sync\Console\PurgeUnusedInvitesCommand;
use He4rt\IntegrationDiscord\Sync\Console\SyncDiscordGuildCommand;
use He4rt\IntegrationDiscord\Sync\Observers\DiscordEventLogObserver;
use He4rt\IntegrationDiscord\Transport\DiscordConnector;
use He4rt\IntegrationDiscord\Transport\DiscordOAuthConnector;
use Illuminate\Support\ServiceProvider;

class IntegrationDiscordServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Quem responde "desde quando essa pessoa está na comunidade" para o
        // slide da tag: a data mora em discord_members, então o dono dela é aqui.
        $this->app->bind(MembershipDates::class, DiscordMembershipDates::class);

        $this->app->singleton(DiscordConnector::class, fn (): DiscordConnector => new DiscordConnector(
            botToken: config()->string('discord.token'),
        ));

        $this->app->singleton(DiscordOAuthConnector::class, fn (): DiscordOAuthConnector => new DiscordOAuthConnector(
            clientId: config()->string('services.discord.client_id'),
            clientSecret: config()->string('services.discord.client_secret'),
            redirectUri: config()->string('services.discord.redirect_uri'),
        ));
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'integration-discord');

        DiscordEventLog::observe(DiscordEventLogObserver::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                ImportDiscordProfilesCommand::class,
                ImportDiscordMessagesCommand::class,
                MergeDuplicateDiscordProfilesCommand::class,
                SyncDiscordGuildCommand::class,
                BackfillVoiceLogsCommand::class,
                PurgeUnusedInvitesCommand::class,
            ]);
        }
    }
}
