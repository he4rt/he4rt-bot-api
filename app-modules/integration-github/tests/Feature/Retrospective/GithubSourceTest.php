<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use He4rt\Community\Retrospective\DTOs\Period;
use He4rt\Community\Retrospective\DTOs\SourceFilters;
use He4rt\Community\Retrospective\DTOs\SourceResult;
use He4rt\Community\Retrospective\Enums\ExclusionKind;
use He4rt\IntegrationGithub\Enums\ContributionType;
use He4rt\IntegrationGithub\Models\GithubContribution;
use He4rt\IntegrationGithub\Retrospective\GithubSource;

/**
 * Golden test do cálculo do GitHub. Herdado do antigo read model do portal
 * (CommunityRetrospective), agora validando o GithubSource: mesma agregação,
 * mesmos números. Os filtros ricos do visitante (tipo/repo/desfecho/pessoa/sort)
 * foram aposentados na Fase 1, então seus casos saíram.
 */
beforeEach(function (): void {
    $this->since = CarbonImmutable::parse('2026-06-01 00:00:00');
    $this->until = CarbonImmutable::parse('2026-06-07 23:59:59');
    $this->collect = fn (bool $hideBots = true): SourceResult => new GithubSource()->collect(
        Period::of($this->since, $this->until),
        new SourceFilters(hideBots: $hideBots),
    );
});

/**
 * @param  array<string, mixed>  $attributes
 */
function ghContribution(array $attributes): GithubContribution
{
    return GithubContribution::factory()->create($attributes);
}

/**
 * @return array<string, mixed>
 */
function ghSlide(SourceResult $result, string $kind): array
{
    foreach ($result->slides as $slide) {
        if ($slide->kind() === $kind) {
            return $slide->toArray();
        }
    }

    return [];
}

/**
 * @return array<string, int>
 */
function ghMeta(SourceResult $result): array
{
    return ghSlide($result, 'github.panorama')['meta'] ?? [];
}

/**
 * @return list<array<string, mixed>>
 */
function ghPeople(SourceResult $result): array
{
    return ghSlide($result, 'github.core')['people'] ?? [];
}

/**
 * @return list<array<string, mixed>>
 */
function ghRepos(SourceResult $result): array
{
    return collect($result->slides)
        ->filter(fn ($slide): bool => $slide->kind() === 'github.repos')
        ->map(fn ($slide): array => $slide->toArray()['repo'])
        ->values()
        ->all();
}

/**
 * @return list<array<string, mixed>>
 */
function ghHighlights(SourceResult $result): array
{
    return ghSlide($result, 'github.highlights')['highlights'] ?? [];
}

it('identifica-se como a fonte github', function (): void {
    expect(new GithubSource()->key())->toBe('github');
});

it('agrega contribuições por pessoa com contagem por tipo e total, ordenado desc', function (): void {
    ghContribution(['actor_login' => 'maria', 'actor_id' => 42, 'type' => ContributionType::Pr, 'external_ref' => 'pr:1', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'open', 'merged' => false]]);
    ghContribution(['actor_login' => 'maria', 'actor_id' => 42, 'type' => ContributionType::Issue, 'external_ref' => 'issue:1', 'occurred_at' => '2026-06-03']);
    ghContribution(['actor_login' => 'joao', 'actor_id' => 7, 'type' => ContributionType::Review, 'external_ref' => 'review:1', 'occurred_at' => '2026-06-03']);

    $result = ($this->collect)();
    $meta = ghMeta($result);
    $people = ghPeople($result);

    expect($meta['people'])->toBe(2)
        ->and($meta['total'])->toBe(3)
        ->and($meta['days'])->toBe(7)
        ->and($people[0]['login'])->toBe('maria')
        ->and($people[0]['total'])->toBe(2)
        ->and($people[0]['prs'])->toBe(1)
        ->and($people[0]['issues'])->toBe(1)
        ->and($people[0]['avatar'])->toContain('42');
});

it('exclui bots do ranking', function (): void {
    ghContribution(['actor_login' => 'dependabot[bot]', 'type' => ContributionType::Pr, 'external_ref' => 'pr:9', 'occurred_at' => '2026-06-02', 'metadata' => ['is_bot' => true, 'state' => 'open', 'merged' => false]]);
    ghContribution(['actor_login' => 'maria', 'type' => ContributionType::Pr, 'external_ref' => 'pr:1', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'open', 'merged' => false]]);

    $result = ($this->collect)();

    expect(ghMeta($result)['people'])->toBe(1)
        ->and(ghPeople($result)[0]['login'])->toBe('maria');
});

it('mantém bots quando hideBots é falso', function (): void {
    ghContribution(['actor_login' => 'dependabot[bot]', 'type' => ContributionType::Pr, 'external_ref' => 'pr:9', 'occurred_at' => '2026-06-02', 'metadata' => ['is_bot' => true, 'state' => 'open', 'merged' => false]]);
    ghContribution(['actor_login' => 'maria', 'type' => ContributionType::Pr, 'external_ref' => 'pr:1', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'open', 'merged' => false]]);

    expect(ghMeta(($this->collect)(false))['people'])->toBe(2);
});

it('inclui PRs fechados sem merge no total, distinguindo por desfecho', function (): void {
    ghContribution(['actor_login' => 'a', 'type' => ContributionType::Pr, 'external_ref' => 'pr:1', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'closed', 'merged' => false]]);
    ghContribution(['actor_login' => 'a', 'type' => ContributionType::Pr, 'external_ref' => 'pr:2', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'closed', 'merged' => true]]);
    ghContribution(['actor_login' => 'a', 'type' => ContributionType::Pr, 'external_ref' => 'pr:3', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'open', 'merged' => false]]);

    $result = ($this->collect)();
    $meta = ghMeta($result);
    $person = collect(ghPeople($result))->firstWhere('login', 'a');

    expect($meta['prs'])->toBe(3)
        ->and($meta['prs_merged'])->toBe(1)
        ->and($meta['prs_unmerged'])->toBe(1)
        ->and($person['prs'])->toBe(3)
        ->and($person['prs_merged'])->toBe(1)
        ->and($person['prs_unmerged'])->toBe(1)
        ->and($person['total'])->toBe(3);
});

it('conta comentários de issue e de review separadamente', function (): void {
    ghContribution(['actor_login' => 'maria', 'type' => ContributionType::Comment, 'external_ref' => 'comment:1', 'occurred_at' => '2026-06-02']);
    ghContribution(['actor_login' => 'maria', 'type' => ContributionType::ReviewComment, 'external_ref' => 'review_comment:1', 'occurred_at' => '2026-06-02']);

    $result = ($this->collect)();
    $maria = collect(ghPeople($result))->firstWhere('login', 'maria');

    expect(ghMeta($result)['comments'])->toBe(1)
        ->and(ghMeta($result)['review_comments'])->toBe(1)
        ->and($maria['comments'])->toBe(1)
        ->and($maria['review_comments'])->toBe(1)
        ->and($maria['total'])->toBe(2);
});

it('respeita a janela de período pelo occurred_at', function (): void {
    ghContribution(['actor_login' => 'maria', 'type' => ContributionType::Issue, 'external_ref' => 'issue:1', 'occurred_at' => '2026-05-30']);
    ghContribution(['actor_login' => 'maria', 'type' => ContributionType::Issue, 'external_ref' => 'issue:2', 'occurred_at' => '2026-06-03']);

    $meta = ghMeta(($this->collect)());

    expect($meta['total'])->toBe(1)
        ->and($meta['issues'])->toBe(1);
});

it('soma additions/deletions/changed_files de PRs em meta e por pessoa', function (): void {
    ghContribution(['actor_login' => 'maria', 'type' => ContributionType::Pr, 'external_ref' => 'pr:1', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'merged', 'merged' => true, 'additions' => 100, 'deletions' => 20, 'changed_files' => 5]]);
    ghContribution(['actor_login' => 'maria', 'type' => ContributionType::Pr, 'external_ref' => 'pr:2', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'open', 'merged' => false, 'additions' => 30, 'deletions' => 4, 'changed_files' => 2]]);
    ghContribution(['actor_login' => 'maria', 'type' => ContributionType::Review, 'external_ref' => 'review:1', 'occurred_at' => '2026-06-02']);

    $result = ($this->collect)();
    $maria = collect(ghPeople($result))->firstWhere('login', 'maria');

    expect(ghMeta($result)['additions'])->toBe(130)
        ->and(ghMeta($result)['deletions'])->toBe(24)
        ->and(ghMeta($result)['changed_files'])->toBe(7)
        ->and($maria['additions'])->toBe(130)
        ->and($maria['deletions'])->toBe(24);
});

it('expõe refs de PR e issue por pessoa para os chips de atividade', function (): void {
    ghContribution(['actor_login' => 'maria', 'type' => ContributionType::Pr, 'external_ref' => 'pr:290', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'merged', 'merged' => true, 'title' => 'feat: x', 'url' => 'https://github.com/he4rt/heartdevs.com/pull/290']]);
    ghContribution(['actor_login' => 'maria', 'type' => ContributionType::Issue, 'external_ref' => 'issue:12', 'occurred_at' => '2026-06-03', 'metadata' => ['title' => 'bug: y', 'url' => 'https://github.com/he4rt/heartdevs.com/issues/12']]);

    $maria = collect(ghPeople(($this->collect)()))->firstWhere('login', 'maria');

    expect($maria['pr_refs'])->toHaveCount(1)
        ->and($maria['pr_refs'][0])->toMatchArray(['num' => 290, 'title' => 'feat: x', 'state' => 'merged'])
        ->and($maria['pr_refs'][0]['url'])->toContain('/pull/290')
        ->and($maria['issue_refs'])->toHaveCount(1)
        ->and($maria['issue_refs'][0])->toMatchArray(['num' => 12, 'title' => 'bug: y']);
});

it('ordena os refs de PR do mais recente para o mais antigo', function (): void {
    ghContribution(['actor_login' => 'maria', 'type' => ContributionType::Pr, 'external_ref' => 'pr:1', 'occurred_at' => '2026-06-01', 'metadata' => ['title' => 'antigo', 'url' => 'u1', 'state' => 'merged']]);
    ghContribution(['actor_login' => 'maria', 'type' => ContributionType::Pr, 'external_ref' => 'pr:2', 'occurred_at' => '2026-06-05', 'metadata' => ['title' => 'recente', 'url' => 'u2', 'state' => 'open']]);
    ghContribution(['actor_login' => 'maria', 'type' => ContributionType::Pr, 'external_ref' => 'pr:3', 'occurred_at' => '2026-06-03', 'metadata' => ['title' => 'meio', 'url' => 'u3', 'state' => 'open']]);

    $maria = collect(ghPeople(($this->collect)()))->firstWhere('login', 'maria');

    expect(array_column($maria['pr_refs'], 'num'))->toBe([2, 3, 1]);
});

it('agrupa PRs por repositório e lista destaques por linhas mudadas', function (): void {
    ghContribution(['actor_login' => 'maria', 'repo' => 'he4rt/heartdevs.com', 'type' => ContributionType::Pr, 'external_ref' => 'pr:1', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'merged', 'merged' => true, 'title' => 'grande', 'url' => 'u1', 'additions' => 500, 'deletions' => 100, 'changed_files' => 10]]);
    ghContribution(['actor_login' => 'joao', 'repo' => 'he4rt/bot', 'type' => ContributionType::Pr, 'external_ref' => 'pr:2', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'open', 'merged' => false, 'title' => 'pequeno', 'url' => 'u2', 'additions' => 10, 'deletions' => 2, 'changed_files' => 1]]);

    $result = ($this->collect)();
    $repos = ghRepos($result);
    $highlights = ghHighlights($result);

    expect($repos)->toHaveCount(2)
        ->and(collect($repos)->firstWhere('full_name', 'he4rt/heartdevs.com')['name'])->toBe('heartdevs.com')
        ->and(collect($repos)->firstWhere('full_name', 'he4rt/heartdevs.com')['prs'])->toHaveCount(1)
        ->and($highlights[0]['num'])->toBe(1)
        ->and($highlights[0]['additions'])->toBe(500)
        ->and($highlights[0]['repo'])->toBe('he4rt/heartdevs.com');
});

it('quebra as pessoas do repo por PRs e churn, autores antes de quem só esteve por perto', function (): void {
    ghContribution(['actor_login' => 'joao', 'repo' => 'he4rt/x', 'type' => ContributionType::Pr, 'external_ref' => 'pr:2', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'open', 'merged' => false, 'title' => 'pequeno', 'url' => 'u2', 'additions' => 10, 'deletions' => 2, 'changed_files' => 1]]);
    ghContribution(['actor_login' => 'ana', 'repo' => 'he4rt/x', 'type' => ContributionType::Review, 'external_ref' => 'review:1', 'occurred_at' => '2026-06-02']);
    ghContribution(['actor_login' => 'maria', 'repo' => 'he4rt/x', 'type' => ContributionType::Pr, 'external_ref' => 'pr:1', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'merged', 'merged' => true, 'title' => 'grande', 'url' => 'u1', 'additions' => 500, 'deletions' => 100, 'changed_files' => 5]]);

    $people = ghRepos(($this->collect)())[0]['people'];

    expect(array_column($people, 'login'))->toBe(['maria', 'joao', 'ana'])
        ->and($people[0]['prs'])->toBe(1)
        ->and($people[0]['additions'])->toBe(500)
        ->and($people[0]['deletions'])->toBe(100)
        ->and($people[2]['prs'])->toBe(0);
});

it('esconde da lista repos sem PR no recorte mas mantém suas contribuições nas stats', function (): void {
    ghContribution(['actor_login' => 'maria', 'repo' => 'he4rt/com-pr', 'type' => ContributionType::Pr, 'external_ref' => 'pr:1', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'merged', 'merged' => true, 'title' => 't', 'url' => 'u', 'additions' => 5, 'deletions' => 1, 'changed_files' => 1]]);
    ghContribution(['actor_login' => 'joao', 'repo' => 'he4rt/so-review', 'type' => ContributionType::Review, 'external_ref' => 'review:1', 'occurred_at' => '2026-06-02']);

    $result = ($this->collect)();

    expect(collect(ghRepos($result))->pluck('full_name')->all())->toBe(['he4rt/com-pr'])
        ->and(ghMeta($result)['reviews'])->toBe(1)
        ->and(ghMeta($result)['repos'])->toBe(1);
});

it('devolve resultado vazio quando não há contribuições no recorte', function (): void {
    expect(($this->collect)()->isEmpty())->toBeTrue();
});

it('monta os chips do cover a partir do meta', function (): void {
    ghContribution(['actor_login' => 'maria', 'type' => ContributionType::Pr, 'external_ref' => 'pr:1', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'merged', 'merged' => true, 'additions' => 10, 'deletions' => 5]]);

    $result = ($this->collect)();
    $labels = collect($result->headline->metrics)->map(fn ($metric): string => $metric->label)->all();

    expect($result->label)->toBe('GitHub')
        ->and($labels)->toContain('Pessoas')
        ->and($labels)->toContain('PRs')
        ->and($labels)->toContain('Linhas');
});

/**
 * Curadoria (Fase 3): a fonte se descreve para o Deck Builder e honra as
 * exclusions no collect — o item excluído sai dos slides E dos números.
 */
it('descreve o catálogo de slides sem tocar o dado', function (): void {
    $catalog = new GithubSource()->slideCatalog();

    expect(collect($catalog)->pluck('kind')->all())
        ->toBe(['github.panorama', 'github.repos', 'github.highlights', 'github.core', 'github.community'])
        ->and($catalog[0]->label)->toBe('Panorama');
});

it('oferece itens e pessoas do recorte como candidatos a exclusion', function (): void {
    ghContribution(['actor_login' => 'maria', 'repo' => 'he4rt/api', 'type' => ContributionType::Pr, 'external_ref' => 'pr:1', 'occurred_at' => '2026-06-02', 'metadata' => ['title' => 'Ajusta login', 'additions' => 80, 'deletions' => 2]]);
    ghContribution(['actor_login' => 'maria', 'type' => ContributionType::Review, 'external_ref' => 'review:9', 'occurred_at' => '2026-06-03']);
    ghContribution(['actor_login' => 'joao', 'type' => ContributionType::Pr, 'external_ref' => 'pr:2', 'occurred_at' => '2026-05-01', 'metadata' => ['additions' => 999]]);

    $candidates = new GithubSource()->exclusionCandidates(Period::of($this->since, $this->until));

    $items = collect($candidates)->filter(fn ($candidate): bool => $candidate->kind === ExclusionKind::Item);
    $people = collect($candidates)->filter(fn ($candidate): bool => $candidate->kind === ExclusionKind::Person);

    // Fora do recorte não vira candidato; review não é item (só PR/issue).
    expect($items->pluck('ref')->all())->toBe(['pr:1'])
        ->and($items->first()->label)->toBe('#1 Ajusta login')
        ->and($items->first()->hint)->toBe('he4rt/api · maria')
        ->and($people->pluck('ref')->all())->toBe(['actor:maria']);
});

it('exclusion de item derruba a contribuição dos slides e dos números', function (): void {
    ghContribution(['actor_login' => 'maria', 'repo' => 'he4rt/api', 'type' => ContributionType::Pr, 'external_ref' => 'pr:1', 'occurred_at' => '2026-06-02', 'metadata' => ['title' => 'fica', 'additions' => 10, 'deletions' => 0]]);
    ghContribution(['actor_login' => 'maria', 'repo' => 'he4rt/api', 'type' => ContributionType::Pr, 'external_ref' => 'pr:2', 'occurred_at' => '2026-06-03', 'metadata' => ['title' => 'sai', 'additions' => 500, 'deletions' => 0]]);

    $result = new GithubSource()->collect(
        Period::of($this->since, $this->until),
        new SourceFilters(exclusions: ['pr:2']),
    );

    expect(ghMeta($result)['prs'])->toBe(1)
        ->and(ghMeta($result)['additions'])->toBe(10)
        ->and(collect(ghSlide($result, 'github.highlights')['highlights'] ?? [])->pluck('title')->all())->toBe(['fica']);
});

it('exclusion de pessoa derruba toda a contribuição dela no recorte', function (): void {
    ghContribution(['actor_login' => 'maria', 'type' => ContributionType::Pr, 'external_ref' => 'pr:1', 'occurred_at' => '2026-06-02', 'metadata' => ['additions' => 10]]);
    ghContribution(['actor_login' => 'spammer', 'type' => ContributionType::Pr, 'external_ref' => 'pr:2', 'occurred_at' => '2026-06-03', 'metadata' => ['additions' => 900]]);
    ghContribution(['actor_login' => 'spammer', 'type' => ContributionType::Issue, 'external_ref' => 'issue:7', 'occurred_at' => '2026-06-03']);

    $result = new GithubSource()->collect(
        Period::of($this->since, $this->until),
        new SourceFilters(exclusions: ['actor:spammer']),
    );

    expect(ghMeta($result)['people'])->toBe(1)
        ->and(ghMeta($result)['issues'])->toBe(0)
        ->and(collect(ghPeople($result))->pluck('login')->all())->toBe(['maria']);
});

it('normaliza state closed+merged para merged nos refs de PR', function (): void {
    ghContribution(['actor_login' => 'maria', 'type' => ContributionType::Pr, 'external_ref' => 'pr:1', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'closed', 'merged' => true, 'title' => 'feat: merged', 'url' => 'u1']]);
    ghContribution(['actor_login' => 'maria', 'type' => ContributionType::Pr, 'external_ref' => 'pr:2', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'closed', 'merged' => false, 'title' => 'feat: closed', 'url' => 'u2']]);

    $maria = collect(ghPeople(($this->collect)()))->firstWhere('login', 'maria');

    expect(array_column($maria['pr_refs'], 'state'))->toBe(['merged', 'closed']);
});

it('corta ações pós-merge (occurred_at > merged_at) de um PR mesclado', function (): void {
    ghContribution(['actor_login' => 'maria', 'type' => ContributionType::Pr, 'external_ref' => 'pr:100', 'occurred_at' => '2026-06-02 09:00:00', 'metadata' => ['state' => 'closed', 'merged' => true, 'merged_at' => '2026-06-03T12:00:00Z']]);
    ghContribution(['actor_login' => 'joao', 'type' => ContributionType::Review, 'external_ref' => 'review:1', 'target_ref' => 'pr:100', 'occurred_at' => '2026-06-03 09:00:00', 'metadata' => []]);
    ghContribution(['actor_login' => 'joao', 'type' => ContributionType::Review, 'external_ref' => 'review:2', 'target_ref' => 'pr:100', 'occurred_at' => '2026-06-03 15:00:00', 'metadata' => []]);
    ghContribution(['actor_login' => 'ana', 'type' => ContributionType::ReviewComment, 'external_ref' => 'review_comment:1', 'target_ref' => 'pr:100', 'occurred_at' => '2026-06-04 10:00:00', 'metadata' => ['kind' => 'pr']]);
    ghContribution(['actor_login' => 'ana', 'type' => ContributionType::Comment, 'external_ref' => 'comment:1', 'target_ref' => 'pr:100', 'occurred_at' => '2026-06-04 11:00:00', 'metadata' => ['kind' => 'pr']]);

    $result = ($this->collect)();
    $meta = ghMeta($result);

    expect($meta['total'])->toBe(2)
        ->and($meta['reviews'])->toBe(1)
        ->and($meta['review_comments'])->toBe(0)
        ->and($meta['comments'])->toBe(0);

    $people = ghPeople($result);

    expect(collect($people)->firstWhere('login', 'joao')['total'])->toBe(1)
        ->and(collect($people)->firstWhere('login', 'ana'))->toBeNull();
});

it('não corta quando o PR não está mesclado ou ainda não tem merged_at', function (): void {
    ghContribution(['actor_login' => 'maria', 'type' => ContributionType::Pr, 'external_ref' => 'pr:200', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'open', 'merged' => false, 'merged_at' => null]]);
    ghContribution(['actor_login' => 'joao', 'type' => ContributionType::Review, 'external_ref' => 'review:10', 'target_ref' => 'pr:200', 'occurred_at' => '2026-06-05', 'metadata' => []]);
    ghContribution(['actor_login' => 'maria', 'type' => ContributionType::Pr, 'external_ref' => 'pr:201', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'closed', 'merged' => true]]);
    ghContribution(['actor_login' => 'ana', 'type' => ContributionType::Review, 'external_ref' => 'review:11', 'target_ref' => 'pr:201', 'occurred_at' => '2026-06-05', 'metadata' => []]);

    $meta = ghMeta(($this->collect)());

    expect($meta['total'])->toBe(4)
        ->and($meta['reviews'])->toBe(2);
});

it('descarta PR vazio/de teste (changed_files=0 e não mesclado) de toda a retrospectiva', function (): void {
    ghContribution(['actor_login' => 'leo', 'repo' => 'he4rt/4noobs', 'type' => ContributionType::Pr, 'external_ref' => 'pr:133', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'closed', 'merged' => false, 'additions' => 0, 'deletions' => 0, 'changed_files' => 0]]);
    ghContribution(['actor_login' => 'maria', 'repo' => 'he4rt/4noobs', 'type' => ContributionType::Pr, 'external_ref' => 'pr:134', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'closed', 'merged' => false, 'additions' => 10, 'deletions' => 2, 'changed_files' => 3]]);

    $result = ($this->collect)();
    $meta = ghMeta($result);
    $people = ghPeople($result);

    expect($meta['people'])->toBe(1)
        ->and($meta['prs'])->toBe(1)
        ->and($meta['prs_unmerged'])->toBe(1)
        ->and($meta['total'])->toBe(1)
        ->and(collect($people)->firstWhere('login', 'leo'))->toBeNull()
        ->and($people[0]['login'])->toBe('maria');
});

it('mantém PR mesclado com 0 arquivos e PR sem changed_files gravado', function (): void {
    ghContribution(['actor_login' => 'maria', 'type' => ContributionType::Pr, 'external_ref' => 'pr:1', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'closed', 'merged' => true, 'changed_files' => 0]]);
    ghContribution(['actor_login' => 'joao', 'type' => ContributionType::Pr, 'external_ref' => 'pr:2', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'closed', 'merged' => false]]);

    $meta = ghMeta(($this->collect)());

    expect($meta['prs'])->toBe(2)
        ->and($meta['total'])->toBe(2);
});

it('não quebra a coleta quando merged_at está malformado (trata como sem merge)', function (): void {
    ghContribution(['actor_login' => 'maria', 'type' => ContributionType::Pr, 'external_ref' => 'pr:300', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'closed', 'merged' => true, 'merged_at' => 'not-a-date']]);
    ghContribution(['actor_login' => 'joao', 'type' => ContributionType::Review, 'external_ref' => 'review:30', 'target_ref' => 'pr:300', 'occurred_at' => '2026-06-05', 'metadata' => []]);

    $meta = ghMeta(($this->collect)());

    expect($meta['total'])->toBe(2)
        ->and($meta['reviews'])->toBe(1);
});

it('não corta PR cujo changed_files é null (não coage para zero)', function (): void {
    ghContribution(['actor_login' => 'maria', 'type' => ContributionType::Pr, 'external_ref' => 'pr:301', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'open', 'merged' => false, 'changed_files' => null]]);

    $meta = ghMeta(($this->collect)());

    expect($meta['prs'])->toBe(1)
        ->and($meta['total'])->toBe(1);
});
