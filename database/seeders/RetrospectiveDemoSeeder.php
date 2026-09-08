<?php

declare(strict_types=1);

namespace Database\Seeders;

use Carbon\CarbonImmutable;
use He4rt\Activity\Database\Seeders\DiscordRetrospectiveSeeder;
use He4rt\Community\Database\Seeders\RetrospectiveSeeder;
use He4rt\Community\Retrospective\Models\Retrospective;
use He4rt\IntegrationGithub\Database\Seeders\GithubRetrospectiveSeeder;
use Illuminate\Database\Seeder;

/**
 * Playground da retrospectiva multi-fonte: 13 meses de atividade de GitHub e
 * Discord mais uma edição para cada estado do Deck Builder.
 *
 * A composição cross-module mora aqui, na raiz, porque é o único lugar que pode
 * conhecer os três módulos ao mesmo tempo: `community` é domínio e não importa de
 * `integration-github`; cada módulo semeia apenas o dado que possui.
 *
 * Roda com `php artisan migrate:fresh --seed` (o DatabaseSeeder já chama este) ou
 * isolado com `--class=Database\\Seeders\\RetrospectiveDemoSeeder`. Espera um
 * banco limpo: os seeders inserem em lote, sem upsert.
 */
final class RetrospectiveDemoSeeder extends Seeder
{
    /** Quantos meses de histórico as fontes recebem. */
    private const int HISTORY_MONTHS = 13;

    public function run(): void
    {
        // Sem upsert: rodar duas vezes esbarraria no unique de github_repositories
        // com um erro cru do Postgres. Melhor dizer o que fazer.
        if (Retrospective::query()->exists()) {
            $this->command?->getOutput()->writeln(
                '  <fg=yellow>Playground já semeado.</> <fg=gray>Rode `php artisan migrate:fresh --seed` para recomeçar.</>',
            );

            return;
        }

        $anchor = CarbonImmutable::now()->startOfMonth();

        // Uma única linha do tempo para os três seeders. O `spotlight` é o último
        // mês fechado: é onde as iscas de curadoria são plantadas, para caírem
        // dentro do recorte das edições recentes e aparecerem no picker.
        $timeline = [
            'since' => $anchor->subMonths(self::HISTORY_MONTHS)->toIso8601String(),
            'until' => CarbonImmutable::now()->subDay()->toIso8601String(),
            'spotlightSince' => $anchor->subMonth()->toIso8601String(),
            'spotlightUntil' => $anchor->subSecond()->toIso8601String(),
        ];

        $github = $this->resolve(GithubRetrospectiveSeeder::class);
        $discord = $this->resolve(DiscordRetrospectiveSeeder::class);

        $github->__invoke($timeline);
        $discord->__invoke($timeline);

        // As edições recebem os refs plantados por cada fonte: é o que faz o
        // picker de exclusions e o aviso de republicar terem conteúdo real.
        $this->call(RetrospectiveSeeder::class, parameters: [
            'baits' => [...$github->exclusionBaits(), ...$discord->exclusionBaits()],
        ]);

        $this->guide();
    }

    /**
     * Mapa do que cada edição exercita. Sem isso o operador abre a listagem e vê
     * seis títulos sem saber qual testa o quê.
     */
    private function guide(): void
    {
        $output = $this->command?->getOutput();

        if ($output === null) {
            return;
        }

        $output->writeln('');
        $output->writeln('<options=bold>Playground da retrospectiva</> <fg=gray>· /admin/retrospectives</>');
        $output->writeln('');

        foreach ([
            [RetrospectiveSeeder::RICH_DRAFT, 'rascunho', 'comece aqui: 6 meses de dado, sem curadoria nenhuma'],
            [RetrospectiveSeeder::CURATED_DRAFT, 'rascunho', 'já curado: ordem invertida, cards de repo escondidos, bots à mostra, exclusions aplicadas'],
            [RetrospectiveSeeder::PUBLISHED, 'publicada', 'snapshot congelado pelo pipeline real — veja a página pública'],
            [RetrospectiveSeeder::DRIFTED, 'publicada', 'exclusion mudou depois do publish: o badge "republique" aparece'],
            [RetrospectiveSeeder::PUBLISHING, 'publicando', 'estado transitório: badge de status + poll de 3s no builder'],
            [RetrospectiveSeeder::ARCHIVED, 'rascunho', 'recorte de 2019, sem dado: estados vazios do builder e do preview'],
        ] as [$title, $status, $note]) {
            // A hierarquia vem do "↳", não de indentação: o console do artisan
            // come os espaços à esquerda da linha.
            $output->writeln(sprintf('<fg=cyan>%s</> <fg=gray>(%s)</>', $title, $status));
            $output->writeln(sprintf('<fg=gray>  ↳ %s</>', $note));
        }

        $output->writeln('');
        $output->writeln('<fg=gray>Login:</> admin@admin.com <fg=gray>/</> admin');
        $output->writeln('');
    }
}
