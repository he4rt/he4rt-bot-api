<?php

declare(strict_types=1);

namespace He4rt\IntegrationGithub\Console;

use He4rt\IntegrationGithub\Contributions\ResolveContributorIdentity;
use He4rt\IntegrationGithub\Contributions\TrackGithubContribution;
use He4rt\IntegrationGithub\Models\GithubContribution;
use Illuminate\Console\Command;

/**
 * Projeta o histórico do lake que nenhum evento alcança: as linhas já gravadas não
 * são "recently created", então só um passe explícito as leva ao Tracking.
 */
final class ProjectGithubContributionsCommand extends Command
{
    protected $signature = 'github:project-contributions {--dry-run : Apenas relata, sem escrever}';

    protected $description = 'Projeta contribuições já gravadas no lake como contribuições do Tracking';

    public function handle(
        TrackGithubContribution $producer,
        ResolveContributorIdentity $resolveIdentity,
    ): int {
        $dryRun = (bool) $this->option('dry-run');

        $total = GithubContribution::query()->count();
        $claimable = 0;
        $skipped = 0;

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        GithubContribution::query()->chunkById(500, function ($contributions) use (
            $producer,
            $resolveIdentity,
            $dryRun,
            &$claimable,
            &$skipped,
            $bar,
        ): void {
            foreach ($contributions as $contribution) {
                $resolved = $resolveIdentity->handle($contribution);

                if ($resolved === null) {
                    $skipped++;
                    $bar->advance();

                    continue;
                }

                $claimable++;

                if (!$dryRun) {
                    $producer->adopt($contribution);
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->table(['', 'Linhas'], [
            ['No lake', $total],
            ['Reivindicáveis', $claimable],
            ['Sem identidade conectada', $skipped],
        ]);

        $this->line($dryRun
            ? '<comment>Dry-run: nada foi escrito.</comment>'
            : '<info>Projeção concluída.</info>');

        return self::SUCCESS;
    }
}
