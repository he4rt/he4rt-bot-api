<?php

declare(strict_types=1);

namespace He4rt\IntegrationGithub\Database\Seeders;

use Carbon\CarbonImmutable;
use He4rt\IntegrationGithub\Enums\ContributionType;
use He4rt\IntegrationGithub\Models\GithubContribution;
use He4rt\IntegrationGithub\Models\GithubRepository;
use He4rt\IntegrationGithub\Retrospective\GithubSource;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Popula a fonte GitHub da retrospectiva com um recorte plausível de comunidade:
 * repositórios com perfis diferentes, dezenas de pessoas com volumes desiguais,
 * bots e PRs de todos os estados.
 *
 * Não cria retrospectivas — quem monta as edições é o RetrospectiveSeeder do
 * community. Aqui só existe o dado que o collect() da fonte lê.
 *
 * Volume vai por insert em lote (não factory por linha): são ~1.300 linhas e o
 * objetivo é um banco de teste em segundos.
 */
final class GithubRetrospectiveSeeder extends Seeder
{
    /**
     * Cada repositório tem um perfil: peso na distribuição de atividade e se
     * recebe PR. Repositório sem PR no recorte NÃO virá como card no deck (mas
     * segue contando em pessoas/issues/comentários) — é justamente o caso de
     * borda do GithubSource que o operador precisa conseguir ver.
     *
     * @var list<array{name: string, weight: int, prs: bool}>
     */
    private const array REPOS = [
        ['name' => 'he4rt/heartdevs.com', 'weight' => 32, 'prs' => true],
        ['name' => 'he4rt/he4rt-bot', 'weight' => 18, 'prs' => true],
        ['name' => 'he4rt/laravel-scylladb', 'weight' => 13, 'prs' => true],
        ['name' => 'he4rt/discord-clustering', 'weight' => 10, 'prs' => true],
        ['name' => 'he4rt/api-heartdevs', 'weight' => 9, 'prs' => true],
        ['name' => 'he4rt/he4rt-oauth', 'weight' => 7, 'prs' => true],
        ['name' => 'he4rt/awesome-he4rt', 'weight' => 7, 'prs' => false],
        ['name' => 'he4rt/site-legacy', 'weight' => 4, 'prs' => false],
    ];

    /**
     * O núcleo aparece no topo de todo ranking; a cauda longa é o que faz o
     * slide "A comunidade" existir (só entra com mais de 5 pessoas).
     *
     * @var list<array{login: string, id: int, weight: int}>
     */
    private const array HUMANS = [
        ['login' => 'danielhe4rt', 'id' => 8_723_431, 'weight' => 26],
        ['login' => 'kaster', 'id' => 1_204_998, 'weight' => 19],
        ['login' => 'Clintonrocha98', 'id' => 5_512_874, 'weight' => 17],
        ['login' => 'jotaonemore', 'id' => 3_398_120, 'weight' => 12],
        ['login' => 'marcelabomfim', 'id' => 7_781_004, 'weight' => 11],
        ['login' => 'lucasnasc', 'id' => 2_240_913, 'weight' => 9],
        ['login' => 'anaparaiso', 'id' => 6_610_772, 'weight' => 8],
        ['login' => 'thiagodev', 'id' => 4_419_338, 'weight' => 8],
        ['login' => 'brunacastro', 'id' => 9_002_155, 'weight' => 7],
        ['login' => 'rafaelmelo', 'id' => 1_872_664, 'weight' => 6],
        ['login' => 'giselletech', 'id' => 5_098_431, 'weight' => 6],
        ['login' => 'pedrolimadev', 'id' => 3_771_209, 'weight' => 5],
        ['login' => 'juliaoliver', 'id' => 8_120_447, 'weight' => 5],
        ['login' => 'vinizago', 'id' => 2_665_882, 'weight' => 4],
        ['login' => 'camilaqa', 'id' => 7_334_015, 'weight' => 4],
        ['login' => 'ericksongomes', 'id' => 4_902_338, 'weight' => 3],
        ['login' => 'natalyabraga', 'id' => 6_128_770, 'weight' => 3],
        ['login' => 'guilhermesr', 'id' => 1_559_204, 'weight' => 3],
        ['login' => 'leticiamartins', 'id' => 8_845_612, 'weight' => 2],
        ['login' => 'fabiosouza', 'id' => 2_099_733, 'weight' => 2],
        ['login' => 'renatoqueiroz', 'id' => 5_776_180, 'weight' => 2],
        ['login' => 'isabelaramos', 'id' => 3_012_998, 'weight' => 2],
        ['login' => 'matheusfrontend', 'id' => 9_441_027, 'weight' => 1],
        ['login' => 'sarahdevops', 'id' => 4_188_365, 'weight' => 1],
        ['login' => 'joaquimtorres', 'id' => 7_620_441, 'weight' => 1],
        ['login' => 'helenagomes', 'id' => 6_355_909, 'weight' => 1],
    ];

    /**
     * Dois caminhos de detecção de bot convivem no GithubSource: sufixo "[bot]"
     * no login e a flag `is_bot` no metadata. Os dois estão representados para o
     * toggle "ocultar bots" ser testável de verdade.
     *
     * @var list<array{login: string, id: int, flag: bool}>
     */
    private const array BOTS = [
        ['login' => 'dependabot[bot]', 'id' => 49_699_333, 'flag' => true],
        ['login' => 'github-actions[bot]', 'id' => 41_898_282, 'flag' => false],
        ['login' => 'renovate[bot]', 'id' => 29_139_614, 'flag' => false],
    ];

    /** @var list<string> */
    private const array PR_TITLES = [
        'feat(identity): merge de contas OAuth sem perder XP',
        'feat(economy): carteira com extrato paginado',
        'fix(bot-discord): timeout do slash command em guilds grandes',
        'refactor(gamification): XP por sessão de voz vira Action',
        'feat(moderation): fila de apelações com SLA visível',
        'fix(activity): sent_at passa a ser timestamptz',
        'feat(events): inscrição em evento com lista de espera',
        'chore(ci): matriz de PHP 8.4 e 8.5',
        'feat(profile): página pública com domínio próprio',
        'fix(identity): remember me em login por OAuth',
        'feat(panel-admin): cluster de Discord no painel',
        'refactor(activity): ETL de mensagens em chunks',
        'feat(squads): ranking semanal por squad',
        'fix(economy): arredondamento na conversão de moedas',
        'feat(onboarding): trilha guiada para quem chega',
        'perf(activity): índice composto em (channel_id, sent_at)',
        'feat(integration-twitch): webhook de live no ar',
        'fix(panel-app): avatar quebrado sem external identity',
        'feat(community): retrospectiva multi-fonte',
        'refactor(moderation): enforcement por policy',
        'feat(integration-devto): sincroniza artigos publicados',
        'fix(bot-discord): reconexão após gateway resume',
        'feat(he4rt): design tokens no tema do Filament',
        'chore(deps): sobe Filament para a v5',
        'feat(events): certificado em PDF pós-evento',
        'fix(squads): convite expirado ainda aceitava membro',
        'refactor(identity): credenciais em value object',
        'feat(panel-admin): auditoria de ações sensíveis',
        'fix(gamification): temporada encerrada zerava ranking',
        'feat(portal): landing da comunidade responsiva',
    ];

    /** @var list<string> */
    private const array ISSUE_TITLES = [
        'XP de voz não conta quando o bot cai no meio da call',
        'Ranking semanal some depois de virar a temporada',
        'Precisamos de dark mode no painel público',
        'Erro 500 ao conectar Twitch com escopo antigo',
        'Documentar como rodar o bot local sem Docker',
        'Onboarding não avisa que o Discord é obrigatório',
        'Timezone errado no extrato da carteira (-3h)',
        'Autocomplete do slash command /perfil não pagina',
        'Duplicidade de mensagem no ETL quando reprocessa',
        'Badge de doador não aparece no perfil público',
        'Melhorar mensagem de erro no merge de contas',
        'Retrospectiva: permitir esconder um PR do deck',
    ];

    /**
     * O PR-monstro: entra no topo dos destaques e do picker de exclusions, que é
     * exatamente o caso que motivou a curadoria (um lockfile gigante roubando a
     * cena de contribuição de verdade).
     */
    private const string MONSTER_PR_REF = 'pr:9001';

    /** Ator de spam: cria o candidato do tipo "pessoa" no picker. */
    private const string SPAM_ACTOR = 'cripto-shill';

    /**
     * Contador de número por repositório: PR e issue compartilham a numeração,
     * como no GitHub de verdade.
     *
     * @var array<string, int>
     */
    private array $numbers = [];

    /**
     * Refs plantados de propósito para o Deck Builder ter o que curar. O
     * RetrospectiveSeeder recebe isso do orquestrador — nenhum módulo de domínio
     * precisa conhecer este.
     *
     * @return array<string, list<string>>
     */
    public function exclusionBaits(): array
    {
        return [
            resolve(GithubSource::class)->key() => [
                self::MONSTER_PR_REF,
                'actor:'.self::SPAM_ACTOR,
            ],
        ];
    }

    /**
     * O recorte `spotlight` é onde as iscas de curadoria são plantadas: precisa
     * ser um intervalo que caia dentro das edições recentes, senão o picker do
     * Deck Builder não tem o que oferecer. Quem manda é o orquestrador — o
     * padrão é o último mês fechado.
     */
    public function run(
        ?string $since = null,
        ?string $until = null,
        ?string $spotlightSince = null,
        ?string $spotlightUntil = null,
    ): void {
        $window = $this->window($since, $until);
        $spotlight = $this->window(
            $spotlightSince ?? (string) CarbonImmutable::now()->startOfMonth()->subMonth(),
            $spotlightUntil ?? (string) CarbonImmutable::now()->startOfMonth()->subSecond(),
        );

        $this->repositories();

        $rows = [
            ...$this->pullRequests($window),
            ...$this->reviews($window),
            ...$this->issues($window),
            ...$this->comments($window),
            ...$this->commits($window),
            ...$this->botActivity($window),
            ...$this->bait($spotlight),
        ];

        foreach (array_chunk($rows, 500) as $chunk) {
            GithubContribution::query()->insert($chunk);
        }

        $this->command?->getOutput()->writeln(sprintf(
            '  <fg=gray>GitHub:</> %d contribuições · %d repositórios · %d pessoas (+%d bots)',
            count($rows),
            count(self::REPOS),
            count(self::HUMANS) + 1,
            count(self::BOTS),
        ));
    }

    private function repositories(): void
    {
        foreach (self::REPOS as $repo) {
            GithubRepository::factory()->backfilled()->create(['full_name' => $repo['name']]);
        }
    }

    /**
     * @param  array{since: CarbonImmutable, until: CarbonImmutable}  $window
     * @return list<array<string, mixed>>
     */
    private function pullRequests(array $window): array
    {
        $rows = [];

        foreach (range(1, 240) as $ignored) {
            $repo = $this->repo(withPrs: true);
            $actor = $this->human();
            $number = $this->nextNumber($repo);
            $state = fake()->randomElement(['merged', 'merged', 'merged', 'merged', 'merged', 'open', 'closed']);

            $rows[] = $this->row(
                repo: $repo,
                actor: $actor,
                type: ContributionType::Pr,
                ref: ContributionType::Pr->ref($number),
                occurredAt: $this->moment($window),
                metadata: $this->prMetadata($repo, $number, $state),
            );
        }

        return $rows;
    }

    /**
     * @param  array{since: CarbonImmutable, until: CarbonImmutable}  $window
     * @return list<array<string, mixed>>
     */
    private function reviews(array $window): array
    {
        $rows = [];

        foreach (range(1, 280) as $index) {
            $repo = $this->repo();
            $actor = $this->human();

            $rows[] = $this->row(
                repo: $repo,
                actor: $actor,
                type: ContributionType::Review,
                ref: ContributionType::Review->ref(300_000 + $index),
                occurredAt: $this->moment($window),
                targetRef: ContributionType::Pr->ref(fake()->numberBetween(120, 900)),
                metadata: ['state' => fake()->randomElement(['approved', 'approved', 'changes_requested', 'commented'])],
            );
        }

        return $rows;
    }

    /**
     * @param  array{since: CarbonImmutable, until: CarbonImmutable}  $window
     * @return list<array<string, mixed>>
     */
    private function issues(array $window): array
    {
        $rows = [];

        foreach (range(1, 96) as $ignored) {
            $repo = $this->repo();
            $actor = $this->human();
            $number = $this->nextNumber($repo);

            $rows[] = $this->row(
                repo: $repo,
                actor: $actor,
                type: ContributionType::Issue,
                ref: ContributionType::Issue->ref($number),
                occurredAt: $this->moment($window),
                metadata: [
                    'title' => fake()->randomElement(self::ISSUE_TITLES),
                    'url' => 'https://github.com/'.$repo.'/issues/'.$number,
                    'state' => fake()->randomElement(['closed', 'closed', 'open']),
                ],
            );
        }

        return $rows;
    }

    /**
     * @param  array{since: CarbonImmutable, until: CarbonImmutable}  $window
     * @return list<array<string, mixed>>
     */
    private function comments(array $window): array
    {
        $rows = [];

        foreach (range(1, 200) as $index) {
            $repo = $this->repo();

            $rows[] = $this->row(
                repo: $repo,
                actor: $this->human(),
                type: ContributionType::Comment,
                ref: ContributionType::Comment->ref(700_000 + $index),
                occurredAt: $this->moment($window),
                targetRef: ContributionType::Issue->ref(fake()->numberBetween(120, 900)),
            );
        }

        foreach (range(1, 150) as $index) {
            $repo = $this->repo();

            $rows[] = $this->row(
                repo: $repo,
                actor: $this->human(),
                type: ContributionType::ReviewComment,
                ref: ContributionType::ReviewComment->ref(800_000 + $index),
                occurredAt: $this->moment($window),
                targetRef: ContributionType::Pr->ref(fake()->numberBetween(120, 900)),
            );
        }

        return $rows;
    }

    /**
     * Commits NÃO carregam additions/deletions: o GithubSource soma essas chaves
     * sobre TODAS as contribuições, então repeti-las em commit inflaria o total
     * de linhas do painel em cima do mesmo diff dos PRs.
     *
     * @param  array{since: CarbonImmutable, until: CarbonImmutable}  $window
     * @return list<array<string, mixed>>
     */
    private function commits(array $window): array
    {
        $rows = [];

        foreach (range(1, 320) as $ignored) {
            $repo = $this->repo();
            $sha = mb_substr(fake()->sha1(), 0, 7);

            $rows[] = $this->row(
                repo: $repo,
                actor: $this->human(),
                type: ContributionType::Commit,
                ref: ContributionType::Commit->ref($sha),
                occurredAt: $this->moment($window),
                metadata: ['sha' => $sha, 'message' => fake()->randomElement(self::PR_TITLES)],
            );
        }

        return $rows;
    }

    /**
     * @param  array{since: CarbonImmutable, until: CarbonImmutable}  $window
     * @return list<array<string, mixed>>
     */
    private function botActivity(array $window): array
    {
        $rows = [];

        foreach (self::BOTS as $bot) {
            foreach (range(1, 22) as $ignored) {
                $repo = $this->repo(withPrs: true);
                $number = $this->nextNumber($repo);
                $isPr = fake()->boolean(70);

                $metadata = $isPr
                    ? $this->prMetadata($repo, $number, 'merged', bump: true)
                    : ['state' => 'commented'];

                if ($bot['flag']) {
                    $metadata['is_bot'] = true;
                }

                $rows[] = $this->row(
                    repo: $repo,
                    actor: ['login' => $bot['login'], 'id' => $bot['id'], 'weight' => 1],
                    type: $isPr ? ContributionType::Pr : ContributionType::Review,
                    ref: $isPr
                        ? ContributionType::Pr->ref($number)
                        : ContributionType::Review->ref(500_000 + fake()->unique()->numberBetween(1, 90_000)),
                    occurredAt: $this->moment($window),
                    metadata: $metadata,
                );
            }
        }

        return $rows;
    }

    /**
     * @param  array{since: CarbonImmutable, until: CarbonImmutable}  $spotlight
     * @return list<array<string, mixed>>
     */
    private function bait(array $spotlight): array
    {
        $repo = self::REPOS[0]['name'];

        $rows = [
            $this->row(
                repo: $repo,
                actor: ['login' => 'danielhe4rt', 'id' => 8_723_431, 'weight' => 1],
                type: ContributionType::Pr,
                ref: self::MONSTER_PR_REF,
                occurredAt: $this->moment($spotlight),
                metadata: [
                    'title' => 'chore(deps): regenera lockfile e snapshots do build',
                    'url' => 'https://github.com/'.$repo.'/pull/9001',
                    'state' => 'closed',
                    'merged' => true,
                    'additions' => 18_420,
                    'deletions' => 9_310,
                    'changed_files' => 412,
                ],
            ),
        ];

        // O ator de spam: volume suficiente para chegar ao topo do picker de
        // pessoas, com um PR ruidoso que suja os destaques.
        foreach (range(1, 14) as $index) {
            $rows[] = $this->row(
                repo: $repo,
                actor: ['login' => self::SPAM_ACTOR, 'id' => 9_999_001, 'weight' => 1],
                type: $index <= 2 ? ContributionType::Pr : ContributionType::Comment,
                ref: $index <= 2
                    ? ContributionType::Pr->ref(9_100 + $index)
                    : ContributionType::Comment->ref(910_000 + $index),
                occurredAt: $this->moment($spotlight),
                metadata: $index <= 2
                    ? [
                        'title' => 'Ganhe 500 USDT revisando este PR 🚀🚀',
                        'url' => 'https://github.com/'.$repo.'/pull/'.(9_100 + $index),
                        'state' => 'closed',
                        'merged' => false,
                        'additions' => 2_400,
                        'deletions' => 12,
                        'changed_files' => 3,
                    ]
                    : [],
            );
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private function prMetadata(string $repo, int $number, string $state, bool $bump = false): array
    {
        $additions = $bump
            ? fake()->numberBetween(1, 90)
            : fake()->numberBetween(6, 1_400);

        return [
            'title' => $bump
                ? 'chore(deps): bump '.fake()->randomElement(['laravel/framework', 'filament/filament', 'pestphp/pest', 'vite'])
                : fake()->randomElement(self::PR_TITLES),
            'url' => 'https://github.com/'.$repo.'/pull/'.$number,
            'state' => $state === 'open' ? 'open' : 'closed',
            'merged' => $state === 'merged',
            'additions' => $additions,
            'deletions' => (int) round($additions * fake()->randomFloat(2, 0.05, 0.7)),
            'changed_files' => max(1, (int) round($additions / fake()->numberBetween(12, 60))),
        ];
    }

    /**
     * @param  array{login: string, id: int, weight: int}  $actor
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private function row(
        string $repo,
        array $actor,
        ContributionType $type,
        string $ref,
        CarbonImmutable $occurredAt,
        ?string $targetRef = null,
        array $metadata = [],
    ): array {
        return [
            'id' => Str::orderedUuid()->toString(),
            'repo' => $repo,
            'actor_login' => $actor['login'],
            'actor_id' => $actor['id'],
            'type' => $type->value,
            'external_ref' => $ref,
            'target_ref' => $targetRef,
            'occurred_at' => $occurredAt,
            'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function nextNumber(string $repo): int
    {
        $this->numbers[$repo] ??= fake()->numberBetween(120, 400);

        return ++$this->numbers[$repo];
    }

    private function repo(bool $withPrs = false): string
    {
        $pool = array_values(array_filter(
            self::REPOS,
            static fn (array $repo): bool => !$withPrs || $repo['prs'],
        ));

        return $this->weighted($pool)['name'];
    }

    /**
     * @return array{login: string, id: int, weight: int}
     */
    private function human(): array
    {
        return $this->weighted(self::HUMANS);
    }

    /**
     * Sorteio com peso: sem isso todo ranking fica plano e o deck perde o
     * contraste entre núcleo e cauda longa.
     *
     * @template T of array{weight: int}
     *
     * @param  list<T>  $items
     * @return T
     */
    private function weighted(array $items): array
    {
        $total = array_sum(array_column($items, 'weight'));
        $draw = fake()->numberBetween(1, $total);

        foreach ($items as $item) {
            $draw -= $item['weight'];

            if ($draw <= 0) {
                return $item;
            }
        }

        return $items[0];
    }

    /**
     * Instante dentro da janela, enviesado para o fim: comunidade cresce, e um
     * deck com atividade uniforme ao longo de um ano não parece real.
     *
     * @param  array{since: CarbonImmutable, until: CarbonImmutable}  $window
     */
    private function moment(array $window): CarbonImmutable
    {
        $span = $window['until']->getTimestamp() - $window['since']->getTimestamp();
        $bias = fake()->randomFloat(4, 0, 1) ** 0.65;

        return $window['since']->addSeconds((int) round($span * $bias));
    }

    /**
     * @return array{since: CarbonImmutable, until: CarbonImmutable}
     */
    private function window(?string $since, ?string $until): array
    {
        return [
            'since' => $since === null
                ? CarbonImmutable::now()->startOfMonth()->subMonths(13)
                : CarbonImmutable::parse($since),
            'until' => $until === null
                ? CarbonImmutable::now()->subDay()
                : CarbonImmutable::parse($until),
        ];
    }
}
