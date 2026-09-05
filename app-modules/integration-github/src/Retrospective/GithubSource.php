<?php

declare(strict_types=1);

namespace He4rt\IntegrationGithub\Retrospective;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Carbon\Exceptions\InvalidFormatException;
use He4rt\Community\Retrospective\Contracts\CuratableSource;
use He4rt\Community\Retrospective\Contracts\MeasuresPerson;
use He4rt\Community\Retrospective\Contracts\RetrospectiveSource;
use He4rt\Community\Retrospective\Contracts\Slide;
use He4rt\Community\Retrospective\DTOs\ExclusionCandidate;
use He4rt\Community\Retrospective\DTOs\HeadlineMetrics;
use He4rt\Community\Retrospective\DTOs\Metric;
use He4rt\Community\Retrospective\DTOs\Period;
use He4rt\Community\Retrospective\DTOs\PersonAccount;
use He4rt\Community\Retrospective\DTOs\PersonIdentity;
use He4rt\Community\Retrospective\DTOs\SlideDescriptor;
use He4rt\Community\Retrospective\DTOs\SourceFilters;
use He4rt\Community\Retrospective\DTOs\SourceResult;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\IntegrationGithub\Enums\ContributionType;
use He4rt\IntegrationGithub\Models\GithubContribution;
use He4rt\IntegrationGithub\Retrospective\Slides\GithubCommunitySlide;
use He4rt\IntegrationGithub\Retrospective\Slides\GithubCoreSlide;
use He4rt\IntegrationGithub\Retrospective\Slides\GithubHighlightsSlide;
use He4rt\IntegrationGithub\Retrospective\Slides\GithubPanoramaSlide;
use He4rt\IntegrationGithub\Retrospective\Slides\GithubRepoSlide;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Fonte GitHub da retrospectiva. Preserva 1:1 o cálculo do antigo read model do
 * portal (filtra bots, conta por occurred_at no período, agrupa por login,
 * quebra PRs merged/unmerged) e o empacota em slides tipados. Repos só entram
 * como card se tiverem PR no recorte; atividade só de review/issue/comentário
 * segue contando em meta/people/highlights.
 */
final class GithubSource implements CuratableSource, MeasuresPerson, RetrospectiveSource
{
    /**
     * Teto das varreduras de curadoria: o picker do Deck Builder mostra os itens
     * e pessoas mais relevantes do recorte, nunca a tabela inteira.
     */
    private const int CANDIDATE_LIMIT = 30;

    public function key(): string
    {
        return 'github';
    }

    public function label(): string
    {
        return 'GitHub';
    }

    public function collect(Period $period, SourceFilters $filters): SourceResult
    {
        $contributions = $this->contributions($period, $filters);

        if ($contributions->isEmpty()) {
            return new SourceResult($this->key(), $this->label(), new HeadlineMetrics(), []);
        }

        /** @var list<array<string, mixed>> $people */
        $people = $contributions
            ->groupBy('actor_login')
            ->map(fn (Collection $items, string $login): array => $this->person($login, $items))
            ->sortByDesc(fn (array $person): int => (int) $person['total'])
            ->values()
            ->all();

        $repos = $this->repos($contributions);
        $highlights = $this->highlights($contributions);

        $meta = [
            'people' => count($people),
            'prs' => $this->countType($contributions, ContributionType::Pr),
            'prs_merged' => $this->countMergedPrs($contributions),
            'prs_unmerged' => $this->countUnmergedPrs($contributions),
            'reviews' => $this->countType($contributions, ContributionType::Review),
            'issues' => $this->countType($contributions, ContributionType::Issue),
            'comments' => $this->countType($contributions, ContributionType::Comment),
            'review_comments' => $this->countType($contributions, ContributionType::ReviewComment),
            'commits' => $this->countType($contributions, ContributionType::Commit),
            'additions' => $this->sumMeta($contributions, 'additions'),
            'deletions' => $this->sumMeta($contributions, 'deletions'),
            'changed_files' => $this->sumMeta($contributions, 'changed_files'),
            // Repos exibidos = só os com PR no recorte (mesmo universo dos cards).
            'repos' => count($repos),
            'total' => $contributions->count(),
            'days' => max(1, (int) ceil($period->since->diffInDays($period->until))),
        ];

        return new SourceResult(
            key: $this->key(),
            label: $this->label(),
            headline: $this->headline($meta),
            slides: $this->slides($meta, $repos, $highlights, $people),
        );
    }

    /**
     * O que esta fonte sabe sobre UMA pessoa no recorte, para o slide da tag He4rt.
     *
     * Casa por `actor_id` e não por login: o número é estável quando alguém
     * renomeia a conta, e é a mesma chave que o identity guarda em
     * `external_account_id` — é ela que liga o Discord ao GitHub sem tabela de
     * tradução nenhuma.
     *
     * @return list<Metric>
     */
    public function measure(PersonIdentity $person, Period $period, SourceFilters $filters): array
    {
        $account = $person->account(IdentityProvider::GitHub->value);

        if (!$account instanceof PersonAccount || !is_numeric($account->accountId)) {
            return [];
        }

        $actorId = (int) $account->accountId;

        /** @var list<Metric> $metrics */
        $metrics = Cache::remember(
            'retrospective.measure.'.$this->key().'.'.$period->cacheKey().'.'.$this->filtersKey($filters).'.'.$actorId,
            now()->addMinutes(5),
            fn (): array => $this->personMetrics($actorId, $period, $filters),
        );

        return $metrics;
    }

    /**
     * @return list<SlideDescriptor>
     */
    public function slideCatalog(): array
    {
        return [
            new SlideDescriptor('github.panorama', 'Panorama', 'Números do recorte inteiro'),
            new SlideDescriptor('github.repos', 'Repositórios', 'Um card por repositório com PR no recorte'),
            new SlideDescriptor('github.highlights', 'Destaques', 'Os 4 PRs maiores do recorte'),
            new SlideDescriptor('github.core', 'O núcleo', 'Quem mais contribuiu'),
            new SlideDescriptor('github.community', 'A comunidade', 'Aparece só com mais de 5 pessoas'),
        ];
    }

    /**
     * @return list<ExclusionCandidate>
     */
    public function exclusionCandidates(Period $period): array
    {
        /** @var list<ExclusionCandidate> $candidates */
        $candidates = Cache::remember(
            'retrospective.candidates.'.$this->key().'.'.$period->cacheKey(),
            now()->addMinutes(5),
            fn (): array => [...$this->itemCandidates($period), ...$this->actorCandidates($period)],
        );

        return $candidates;
    }

    /**
     * As contribuições do recorte já limpas: bots fora, exclusions fora, ruído de
     * pós-merge fora. Um dono só do pipeline — o cartão de uma pessoa precisa
     * contar exatamente o que os slides contaram, senão o mesmo PR apareceria
     * escondido num lugar e somado no outro.
     *
     * @return Collection<int, GithubContribution>
     */
    private function contributions(Period $period, SourceFilters $filters, ?int $actorId = null): Collection
    {
        // Instante do merge por PR (repo + external_ref => merged_at), montado de uma
        // query própria porque o PR-alvo pode ter mesclado FORA do período do recorte.
        $mergedAt = $this->mergedAtIndex();

        /** @var Collection<int, GithubContribution> $contributions */
        $contributions = GithubContribution::query()
            ->whereBetween('occurred_at', [$period->since, $period->until])
            ->when($actorId !== null, fn (Builder $query): Builder => $query->where('actor_id', $actorId))
            ->get()
            ->when(
                $filters->hideBots,
                fn (Collection $items): Collection => $items->reject(fn (GithubContribution $contribution): bool => $this->isBot($contribution)),
            )
            // Exclusion mexe no dado (ADR-0001): o item sai dos slides e também
            // dos números, então é derrubado aqui, antes de qualquer agregação.
            ->reject(fn (GithubContribution $contribution): bool => $this->isExcluded($contribution, $filters))
            ->reject(fn (GithubContribution $contribution): bool => $this->isEmptyTestPr($contribution))
            ->reject(fn (GithubContribution $contribution): bool => $this->isPostMergeNoise($contribution, $mergedAt))
            ->values();

        return $contributions;
    }

    /**
     * Código entregue, código revisado e o tamanho do que mexeu — a leitura mais
     * curta de "essa pessoa sustentou os repositórios". Métrica zerada não entra:
     * "0 reviews" ocupa espaço no cartão para dizer que não há o que dizer.
     *
     * @return list<Metric>
     */
    private function personMetrics(int $actorId, Period $period, SourceFilters $filters): array
    {
        $contributions = $this->contributions($period, $filters, $actorId);

        $metrics = [
            new Metric('PRs', $this->countType($contributions, ContributionType::Pr)),
            new Metric('Reviews', $this->countType($contributions, ContributionType::Review)),
            new Metric('Linhas somadas', $this->sumMeta($contributions, 'additions')),
        ];

        return array_values(array_filter(
            $metrics,
            static fn (Metric $metric): bool => $metric->value > 0,
        ));
    }

    /**
     * Identidade dos filtros na chave de cache. Sem isso, mexer numa exclusion e
     * reabrir o builder em menos de cinco minutos mostraria o número anterior.
     */
    private function filtersKey(SourceFilters $filters): string
    {
        return md5(($filters->hideBots ? '1' : '0').'|'.implode(',', $filters->exclusions));
    }

    /**
     * PRs e issues do recorte, os maiores primeiro (o que aparece nos cards de
     * repositório e nos destaques é justamente o que o operador quer poder
     * esconder). O ref é o próprio external_ref ("pr:142").
     *
     * @return list<ExclusionCandidate>
     */
    private function itemCandidates(Period $period): array
    {
        return array_values(
            GithubContribution::query()
                ->whereBetween('occurred_at', [$period->since, $period->until])
                ->whereIn('type', [ContributionType::Pr, ContributionType::Issue])
                ->orderByRaw("COALESCE((metadata->>'additions')::int, 0) + COALESCE((metadata->>'deletions')::int, 0) DESC")
                ->limit(self::CANDIDATE_LIMIT)
                ->get(['external_ref', 'repo', 'actor_login', 'metadata'])
                ->map(function (GithubContribution $contribution): ExclusionCandidate {
                    $metadata = $contribution->metadata ?? [];
                    $title = (string) ($metadata['title'] ?? '');

                    return ExclusionCandidate::item(
                        ref: $contribution->external_ref,
                        label: '#'.$this->refNumber($contribution->external_ref).($title === '' ? '' : ' '.$title),
                        hint: $contribution->repo.' · '.$contribution->actor_login,
                    );
                })
                ->all(),
        );
    }

    /**
     * @return list<ExclusionCandidate>
     */
    private function actorCandidates(Period $period): array
    {
        return array_values(
            GithubContribution::query()
                ->whereBetween('occurred_at', [$period->since, $period->until])
                ->groupBy('actor_login')
                ->orderByRaw('COUNT(*) DESC')
                ->limit(self::CANDIDATE_LIMIT)
                ->get(['actor_login', DB::raw('COUNT(*) AS total')])
                ->map(fn (GithubContribution $row): ExclusionCandidate => ExclusionCandidate::person(
                    ref: 'actor:'.$row->actor_login,
                    label: $row->actor_login,
                    hint: $row->getAttribute('total').' contribuições',
                ))
                ->all(),
        );
    }

    private function isExcluded(GithubContribution $contribution, SourceFilters $filters): bool
    {
        if ($filters->excludes($contribution->external_ref)) {
            return true;
        }

        return $filters->excludes('actor:'.$contribution->actor_login);
    }

    /**
     * @param  array<string, int>  $meta
     * @param  list<array<string, mixed>>  $repos
     * @param  list<array<string, mixed>>  $highlights
     * @param  list<array<string, mixed>>  $people
     * @return list<Slide>
     */
    private function slides(array $meta, array $repos, array $highlights, array $people): array
    {
        $slides = [new GithubPanoramaSlide($meta)];

        foreach ($repos as $i => $repo) {
            $slides[] = new GithubRepoSlide($repo, $i + 1);
        }

        if ($highlights !== []) {
            $slides[] = new GithubHighlightsSlide($highlights);
        }

        if ($people !== []) {
            $slides[] = new GithubCoreSlide($people);

            if (count($people) > 5) {
                $slides[] = new GithubCommunitySlide($people);
            }
        }

        return $slides;
    }

    /**
     * @param  array<string, int>  $meta
     */
    private function headline(array $meta): HeadlineMetrics
    {
        return new HeadlineMetrics([
            new Metric('Pessoas', $meta['people']),
            new Metric('PRs', $meta['prs']),
            new Metric('Reviews', $meta['reviews']),
            new Metric('Issues', $meta['issues']),
            new Metric('Commits', $meta['commits']),
            new Metric('Linhas', $meta['additions'] + $meta['deletions']),
        ]);
    }

    /**
     * @param  Collection<int, GithubContribution>  $items
     * @return array<string, mixed>
     */
    private function person(string $login, Collection $items): array
    {
        $actorId = $items->first()?->actor_id;

        return [
            'login' => $login,
            'avatar' => $this->avatar($login, $actorId),
            'url' => 'https://github.com/'.$login,
            'prs' => $this->countType($items, ContributionType::Pr),
            'prs_merged' => $this->countMergedPrs($items),
            'prs_unmerged' => $this->countUnmergedPrs($items),
            'reviews' => $this->countType($items, ContributionType::Review),
            'issues' => $this->countType($items, ContributionType::Issue),
            'comments' => $this->countType($items, ContributionType::Comment),
            'review_comments' => $this->countType($items, ContributionType::ReviewComment),
            'commits' => $this->countType($items, ContributionType::Commit),
            'additions' => $this->sumMeta($items, 'additions'),
            'deletions' => $this->sumMeta($items, 'deletions'),
            'pr_refs' => $this->prRefs($items),
            'issue_refs' => $this->issueRefs($items),
            'total' => $items->count(),
        ];
    }

    /**
     * @param  Collection<int, GithubContribution>  $items
     */
    private function countType(Collection $items, ContributionType $type): int
    {
        return $items->filter(fn (GithubContribution $contribution): bool => $contribution->type === $type)->count();
    }

    /**
     * @param  Collection<int, GithubContribution>  $items
     */
    private function sumMeta(Collection $items, string $key): int
    {
        return (int) $items->sum(static function (GithubContribution $contribution) use ($key): int {
            $metadata = $contribution->metadata ?? [];

            return (int) ($metadata[$key] ?? 0);
        });
    }

    /**
     * @param  Collection<int, GithubContribution>  $items
     * @return list<array{num: int, title: string, url: string|null, state: string|null}>
     */
    private function prRefs(Collection $items): array
    {
        return array_values($items
            ->filter(fn (GithubContribution $contribution): bool => $contribution->type === ContributionType::Pr)
            ->sortByDesc(fn (GithubContribution $contribution): mixed => $contribution->occurred_at)
            ->map(function (GithubContribution $contribution): array {
                $metadata = $contribution->metadata ?? [];
                $url = $metadata['url'] ?? null;

                return [
                    'num' => $this->refNumber($contribution->external_ref),
                    'title' => (string) ($metadata['title'] ?? ''),
                    'url' => is_string($url) ? $url : null,
                    'state' => $this->prState($metadata),
                ];
            })
            ->values()
            ->all());
    }

    /**
     * @param  Collection<int, GithubContribution>  $items
     * @return list<array{num: int, title: string, url: string|null}>
     */
    private function issueRefs(Collection $items): array
    {
        return array_values($items
            ->filter(fn (GithubContribution $contribution): bool => $contribution->type === ContributionType::Issue)
            ->sortByDesc(fn (GithubContribution $contribution): mixed => $contribution->occurred_at)
            ->map(function (GithubContribution $contribution): array {
                $metadata = $contribution->metadata ?? [];
                $url = $metadata['url'] ?? null;

                return [
                    'num' => $this->refNumber($contribution->external_ref),
                    'title' => (string) ($metadata['title'] ?? ''),
                    'url' => is_string($url) ? $url : null,
                ];
            })
            ->values()
            ->all());
    }

    private function refNumber(string $externalRef): int
    {
        $parts = explode(':', $externalRef);

        return (int) ($parts[1] ?? 0);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function prState(array $metadata): ?string
    {
        if (($metadata['merged'] ?? false) === true) {
            return 'merged';
        }

        $state = $metadata['state'] ?? null;

        return is_string($state) ? $state : null;
    }

    private function avatar(string $login, ?int $actorId): string
    {
        return $actorId !== null
            ? 'https://avatars.githubusercontent.com/u/'.$actorId.'?v=4'
            : 'https://github.com/'.$login.'.png';
    }

    /**
     * @return array{num: int, title: string, url: string|null, state: string|null, author_login: string, additions: int, deletions: int, changed_files: int}
     */
    private function prRow(GithubContribution $contribution): array
    {
        $metadata = $contribution->metadata ?? [];
        $url = $metadata['url'] ?? null;

        return [
            'num' => $this->refNumber($contribution->external_ref),
            'title' => (string) ($metadata['title'] ?? ''),
            'url' => is_string($url) ? $url : null,
            'state' => $this->prState($metadata),
            'author_login' => $contribution->actor_login,
            'additions' => (int) ($metadata['additions'] ?? 0),
            'deletions' => (int) ($metadata['deletions'] ?? 0),
            'changed_files' => (int) ($metadata['changed_files'] ?? 0),
        ];
    }

    /**
     * @param  Collection<int, GithubContribution>  $contributions
     * @return list<array<string, mixed>>
     */
    private function repos(Collection $contributions): array
    {
        return array_values($contributions
            ->groupBy('repo')
            ->map(function (Collection $items, string $repo): array {
                $prs = $items->filter(fn (GithubContribution $contribution): bool => $contribution->type === ContributionType::Pr);

                return [
                    'full_name' => $repo,
                    'name' => explode('/', $repo)[1] ?? $repo,
                    'prs' => $prs
                        ->map(fn (GithubContribution $contribution): array => $this->prRow($contribution))
                        ->sortByDesc(fn (array $pr): int => $pr['additions'] + $pr['deletions'])
                        ->values()
                        ->all(),
                    // Quem mais abriu PR primeiro; presença sem PR (review,
                    // issue, comentário) fica no fim com métricas zeradas — o
                    // trilho do slide separa os dois grupos por esse critério.
                    'people' => $items
                        ->groupBy('actor_login')
                        ->map(function (Collection $group, string $login): array {
                            $authored = $group->filter(fn (GithubContribution $contribution): bool => $contribution->type === ContributionType::Pr);

                            return [
                                'login' => $login,
                                'avatar' => $this->avatar($login, $group->first()?->actor_id),
                                'prs' => $authored->count(),
                                'additions' => $this->sumMeta($authored, 'additions'),
                                'deletions' => $this->sumMeta($authored, 'deletions'),
                            ];
                        })
                        ->sort(fn (array $a, array $b): int => [$b['prs'], $b['additions'] + $b['deletions']] <=> [$a['prs'], $a['additions'] + $a['deletions']])
                        ->values()
                        ->all(),
                    'metrics' => [
                        'prs' => $prs->count(),
                        'additions' => $this->sumMeta($prs, 'additions'),
                        'deletions' => $this->sumMeta($prs, 'deletions'),
                        'changed_files' => $this->sumMeta($prs, 'changed_files'),
                    ],
                ];
            })
            // Só entram na retrospectiva repos com ao menos 1 PR no recorte;
            // atividade só de review/issue/comentário some do card (mas segue
            // contando em meta/people/highlights).
            ->filter(fn (array $repo): bool => $repo['metrics']['prs'] > 0)
            ->values()
            ->all());
    }

    /**
     * @param  Collection<int, GithubContribution>  $contributions
     * @return list<array<string, mixed>>
     */
    private function highlights(Collection $contributions): array
    {
        return array_values($contributions
            ->filter(fn (GithubContribution $contribution): bool => $contribution->type === ContributionType::Pr)
            ->map(fn (GithubContribution $contribution): array => [...$this->prRow($contribution), 'repo' => $contribution->repo])
            ->sortByDesc(fn (array $pr): int => $pr['additions'] + $pr['deletions'])
            ->take(4)
            ->values()
            ->all());
    }

    private function isBot(GithubContribution $contribution): bool
    {
        $metadata = $contribution->metadata ?? [];

        return ($metadata['is_bot'] ?? false) === true
            || str_ends_with($contribution->actor_login, '[bot]');
    }

    /**
     * @param  Collection<int, GithubContribution>  $items
     */
    private function countMergedPrs(Collection $items): int
    {
        return $items->filter(fn (GithubContribution $contribution): bool => $this->isMergedPr($contribution))->count();
    }

    /**
     * @param  Collection<int, GithubContribution>  $items
     */
    private function countUnmergedPrs(Collection $items): int
    {
        return $items->filter(fn (GithubContribution $contribution): bool => $this->isUnmergedClosedPr($contribution))->count();
    }

    private function isMergedPr(GithubContribution $contribution): bool
    {
        if ($contribution->type !== ContributionType::Pr) {
            return false;
        }

        $metadata = $contribution->metadata ?? [];

        return ($metadata['merged'] ?? false) === true;
    }

    /**
     * Índice do instante do merge, indexado por repo e external_ref do PR
     * (ex.: ['he4rt/heartdevs.com' => ['pr:304' => CarbonInterface]]). Só PRs com
     * merged_at gravado entram — antes do backfill --full o índice fica vazio e o
     * corte pós-merge vira no-op (degradação graciosa).
     *
     * @return array<string, array<string, CarbonInterface>>
     */
    private function mergedAtIndex(): array
    {
        $prs = GithubContribution::query()
            ->where('type', ContributionType::Pr)
            ->get();

        $map = [];

        foreach ($prs as $pr) {
            $mergedAt = $this->prMergedAt($pr);

            if ($mergedAt instanceof CarbonInterface) {
                $map[$pr->repo][$pr->external_ref] = $mergedAt;
            }
        }

        return $map;
    }

    /**
     * Instante do merge de um PR, ou null quando não é PR merged / ainda sem merged_at
     * gravado. Único ponto que lida com metadata nulo + chave ausente; reusa isMergedPr().
     */
    private function prMergedAt(GithubContribution $pr): ?CarbonInterface
    {
        if (!$this->isMergedPr($pr)) {
            return null;
        }

        $metadata = $pr->metadata ?? [];
        $mergedAt = $metadata['merged_at'] ?? null;

        if (!is_string($mergedAt)) {
            return null;
        }

        // metadata é JSON solto: um merged_at corrompido não pode derrubar a página
        // inteira ao indexar os PRs — trata como "sem merge" (no-op no corte).
        try {
            return CarbonImmutable::parse($mergedAt);
        } catch (InvalidFormatException) {
            return null;
        }
    }

    /**
     * Ruído pós-merge: review/review_comment/comment cujo alvo é um PR merged e que
     * ocorreu DEPOIS do merge. Revisão antes do merge continua contando; corte estrito.
     *
     * @param  array<string, array<string, CarbonInterface>>  $mergedAt
     */
    private function isPostMergeNoise(GithubContribution $contribution, array $mergedAt): bool
    {
        if (!in_array($contribution->type, [ContributionType::Review, ContributionType::ReviewComment, ContributionType::Comment], strict: true)) {
            return false;
        }

        if ($contribution->target_ref === null || !str_starts_with($contribution->target_ref, 'pr:')) {
            return false;
        }

        $merged = $mergedAt[$contribution->repo][$contribution->target_ref] ?? null;

        return $merged !== null && $contribution->occurred_at->gt($merged);
    }

    /**
     * PR vazio/de teste: changed_files gravado como 0 e não mesclado (ex.: PR aberto só
     * para validar o fluxo fork -> branch -> PR). Não é participação real — sai de meta,
     * people, repos e highlights. PR mesclado com 0 arquivos (raro) segue contando; PR
     * fechado COM arquivos continua contando como participação (decisão #10). Só corta
     * quando changed_files está presente: metadata antigo sem a chave é mantido
     * (degradação graciosa, igual ao índice de merged_at).
     */
    private function isEmptyTestPr(GithubContribution $contribution): bool
    {
        if ($contribution->type !== ContributionType::Pr) {
            return false;
        }

        $metadata = $contribution->metadata ?? [];

        if (!array_key_exists('changed_files', $metadata)) {
            return false;
        }

        $merged = ($metadata['merged'] ?? false) === true;
        $changedFiles = $metadata['changed_files'] ?? null;

        // Só zero numérico explícito conta como "vazio"; null/false/''/não-numérico
        // (metadata inconsistente) NÃO viram 0 por coerção e seguem contando.
        return !$merged && is_numeric($changedFiles) && (int) $changedFiles === 0;
    }

    private function isUnmergedClosedPr(GithubContribution $contribution): bool
    {
        if ($contribution->type !== ContributionType::Pr) {
            return false;
        }

        $metadata = $contribution->metadata ?? [];

        return ($metadata['state'] ?? null) === 'closed' && ($metadata['merged'] ?? false) !== true;
    }
}
