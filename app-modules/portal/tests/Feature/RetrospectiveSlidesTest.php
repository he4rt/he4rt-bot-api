<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use He4rt\Community\Retrospective\DTOs\HeadlineMetrics;
use He4rt\Community\Retrospective\DTOs\SourceResult;
use He4rt\Community\Retrospective\Slides\FrozenSlide;
use Illuminate\Support\Facades\Blade;

it('na view, mostra só os 3 primeiros refs e um chip "mais X…"', function (): void {
    $person = [
        'pr_refs' => array_map(
            fn (int $n): array => ['num' => $n, 'title' => 'pr '.$n, 'url' => null, 'state' => 'open'],
            [10, 9, 8, 7, 6],
        ),
        'issue_refs' => [],
        'reviews' => 0,
        'comments' => 0,
        'review_comments' => 0,
        'commits' => 0,
    ];

    $html = Blade::render('<x-portal::retro.activity-chips :person="$person" />', ['person' => $person]);

    expect($html)->toContain('#10')
        ->and($html)->toContain('#9')
        ->and($html)->toContain('#8')
        ->and($html)->not->toContain('#7')
        ->and($html)->not->toContain('#6')
        ->and($html)->toContain('mais 2…');
});

it('no card cheio, os contadores fecham numa régua única e issues viram número', function (): void {
    $person = [
        'pr_refs' => [['num' => 1, 'title' => 'feat: a', 'url' => null, 'state' => 'merged']],
        'issue_refs' => [
            ['num' => 19, 'title' => 'chore: pnpm', 'url' => null],
            ['num' => 18, 'title' => 'feat: sink', 'url' => null],
        ],
        'reviews' => 41,
        'comments' => 8,
        'review_comments' => 3,
        'commits' => 42,
    ];

    $html = Blade::render('<x-portal::retro.activity-chips :person="$person" />', ['person' => $person]);

    expect($html)->toContain('cstats strip')
        ->and($html)->toContain('Abriu PR')
        ->and($html)->not->toContain('Abriu issue')
        ->and($html)->not->toContain('Revisou')
        ->and(mb_substr_count($html, 'class="cstat '))->toBe(5)
        ->and($html)->toContain('<span class="cstat-n">2</span><span class="cstat-l">issues</span>')
        ->and($html)->toContain('<span class="cstat-n">41</span><span class="cstat-l">reviews</span>');
});

it('a barra de composição escala a largura pelo total do topo do trilho', function (): void {
    $person = ['prs' => 10, 'reviews' => 30, 'issues' => 0, 'comments' => 0, 'review_comments' => 0, 'commits' => 10];

    $scaled = Blade::render('<x-portal::retro.composition-bar :person="$person" :max-total="200" />', ['person' => $person]);
    $full = Blade::render('<x-portal::retro.composition-bar :person="$person" />', ['person' => $person]);

    expect($scaled)->toContain('width: 25%')
        ->and($full)->toContain('width: 100%');
});

it('o card compacto da comunidade mostra só ícone + somatória dos tipos com contribuição', function (): void {
    $person = [
        'pr_refs' => [
            ['num' => 1, 'title' => 'feat: a', 'url' => null, 'state' => 'open'],
            ['num' => 2, 'title' => 'feat: b', 'url' => null, 'state' => 'merged'],
        ],
        'issue_refs' => [],
        'reviews' => 5,
        'comments' => 0,
        'review_comments' => 3,
        'commits' => 0,
    ];

    $html = Blade::render('<x-portal::retro.activity-icons :person="$person" />', ['person' => $person]);

    // 3 tipos com n>0: PR(2), review(5), review_comment(3) → 3 pills, sem zeros
    expect(mb_substr_count($html, 'class="cstat '))->toBe(3)
        ->and($html)->toContain('<span class="cstat-n">2</span>')
        ->and($html)->toContain('<span class="cstat-n">5</span>')
        ->and($html)->toContain('<span class="cstat-n">3</span>')
        ->and($html)->not->toContain('feat:');
});

it('o panorama do github pluraliza repositórios conforme a contagem', function (int $repos, string $expected): void {
    $meta = ['people' => 1, 'total' => 1, 'prs' => 1, 'prs_merged' => 1, 'prs_unmerged' => 0, 'reviews' => 0, 'issues' => 0, 'comments' => 0, 'review_comments' => 0, 'commits' => 0, 'additions' => 1, 'deletions' => 0, 'changed_files' => 1, 'repos' => $repos];

    $html = view('portal::retro.slides.github.panorama', ['meta' => $meta])->render();

    expect($html)->toContain($expected);
})->with([
    'singular' => [1, '1 repositório'],
    'plural' => [3, '3 repositórios'],
]);

it('o panorama do github quebra os totais por tipo e por estado de PR', function (): void {
    $meta = [
        'people' => 83, 'total' => 713, 'days' => 32,
        'prs' => 35, 'prs_merged' => 28, 'prs_unmerged' => 0,
        'reviews' => 357, 'issues' => 19, 'comments' => 118, 'review_comments' => 56, 'commits' => 128,
        'additions' => 18_613, 'deletions' => 4_236, 'changed_files' => 389, 'repos' => 6,
    ];

    $html = view('portal::retro.slides.github.panorama', ['meta' => $meta])->render();

    expect($html)->toContain('713')
        ->and($html)->toContain('em <b>32 dias</b>')
        ->and($html)->toContain('bots ficaram de fora')
        ->and($html)->toContain('Por tipo')
        ->and($html)->toContain('reviews')
        ->and($html)->toContain('357')
        ->and($html)->toContain('50%')
        ->and($html)->toContain('<b>28</b> merged')
        ->and($html)->toContain('<b>7</b> abertos')
        ->and($html)->toContain('<b>0</b> fechados sem merge')
        ->and($html)->toContain('+18.613')
        ->and($html)->toContain('−4.236')
        ->and($html)->toContain('389 arquivos')
        ->and($html)->toContain('reviews são')
        ->and($html)->toContain('<b>22,3</b> contribuições por dia');
});

/**
 * @param  list<array{login: string, prs: int, additions: int, deletions: int}>  $people
 * @param  list<array{num: int, author: string, additions: int, deletions: int, state?: string}>  $prs
 * @return array<string, mixed>
 */
function repoFixture(array $people, array $prs): array
{
    return [
        'name' => 'exemplo',
        'full_name' => 'he4rt/exemplo',
        'people' => array_map(fn (array $p): array => [...$p, 'avatar' => 'https://example.com/a.png'], $people),
        'prs' => array_map(fn (array $pr): array => [
            'num' => $pr['num'],
            'title' => 'pr '.$pr['num'],
            'url' => null,
            'state' => $pr['state'] ?? 'merged',
            'author_login' => $pr['author'],
            'additions' => $pr['additions'],
            'deletions' => $pr['deletions'],
            'changed_files' => 2,
        ], $prs),
        'metrics' => [
            'prs' => count($prs),
            'additions' => array_sum(array_column($prs, 'additions')),
            'deletions' => array_sum(array_column($prs, 'deletions')),
            'changed_files' => 2 * count($prs),
        ],
    ];
}

it('repo pequeno vira cards hero e mantém a quebra por pessoa', function (): void {
    $repo = repoFixture(
        people: [
            ['login' => 'maria', 'prs' => 1, 'additions' => 187, 'deletions' => 257],
            ['login' => 'joao', 'prs' => 1, 'additions' => 41, 'deletions' => 0],
        ],
        prs: [
            ['num' => 135, 'author' => 'maria', 'additions' => 187, 'deletions' => 257],
            ['num' => 134, 'author' => 'joao', 'additions' => 41, 'deletions' => 0, 'state' => 'closed'],
        ],
    );

    $html = view('portal::retro.slides.github.repos', ['repo' => $repo, 'index' => 3])->render();

    expect($html)->toContain('is-hero')
        ->and($html)->toContain('fechado')
        ->and($html)->toContain('2 arq.')
        ->and($html)->toContain('Quem mexeu')
        ->and($html)->toContain('+187')
        ->and($html)->toContain('−257');
});

it('repo com autor recorrente mostra a quebra com adições e remoções', function (): void {
    $repo = repoFixture(
        people: [
            ['login' => 'maria', 'prs' => 4, 'additions' => 2_000, 'deletions' => 400],
            ['login' => 'joao', 'prs' => 1, 'additions' => 41, 'deletions' => 0],
            ['login' => 'ana', 'prs' => 0, 'additions' => 0, 'deletions' => 0],
        ],
        prs: array_map(fn (int $n): array => ['num' => $n, 'author' => 'maria', 'additions' => 500, 'deletions' => 100], [1, 2, 3, 4, 5]),
    );

    $html = view('portal::retro.slides.github.repos', ['repo' => $repo, 'index' => 1])->render();

    expect($html)->toContain('Quem mexeu')
        ->and($html)->toContain('4 PRs')
        ->and($html)->toContain('+2.000')
        ->and($html)->toContain('−400')
        ->and($html)->toContain('na revisão e por perto')
        ->and($html)->not->toContain('is-hero');
});

it('repo pequeno com snapshot antigo (people sem métricas) degrada para a pilha de avatares', function (): void {
    $repo = repoFixture(
        people: [],
        prs: [['num' => 1, 'author' => 'maria', 'additions' => 10, 'deletions' => 2]],
    );
    $repo['people'] = [['login' => 'maria', 'avatar' => 'https://example.com/a.png']];

    $html = view('portal::retro.slides.github.repos', ['repo' => $repo, 'index' => 1])->render();

    expect($html)->toContain('avstack')
        ->and($html)->not->toContain('Quem mexeu');
});

it('o panorama do github tolera decks congelados sem a chave days', function (): void {
    $meta = [
        'people' => 2, 'total' => 10,
        'prs' => 3, 'prs_merged' => 2, 'prs_unmerged' => 1,
        'reviews' => 5, 'issues' => 0, 'comments' => 2, 'review_comments' => 0, 'commits' => 0,
        'additions' => 100, 'deletions' => 50, 'changed_files' => 4, 'repos' => 1,
    ];

    $html = view('portal::retro.slides.github.panorama', ['meta' => $meta])->render();

    expect($html)->not->toContain('dias')
        ->and($html)->not->toContain('contribuições por dia')
        ->and($html)->toContain('<b>2</b> merged')
        ->and($html)->toContain('<b>1</b> fechado sem merge');
});

it('o fechamento troca os badges de fonte pelo muro de avatares do github', function (): void {
    $people = [
        ['login' => 'maria', 'avatar' => 'https://avatars.githubusercontent.com/u/42?v=4'],
        ['login' => 'joao', 'avatar' => 'https://avatars.githubusercontent.com/u/7?v=4'],
    ];
    $sources = [new SourceResult('github', 'GitHub', new HeadlineMetrics(), [
        new FrozenSlide('github.panorama', ['meta' => []]),
        new FrozenSlide('github.core', ['people' => $people]),
    ])];

    $html = Blade::render(
        '<x-portal::retro.slides.closing :sources="$sources" :since="$since" :until="$until" />',
        ['sources' => $sources, 'since' => CarbonImmutable::parse('2026-06-01'), 'until' => CarbonImmutable::parse('2026-06-30')],
    );

    expect($html)->toContain('closing-wall')
        ->and($html)->toContain('avatars.githubusercontent.com/u/42')
        ->and($html)->toContain('alt="joao"')
        ->and($html)->not->toContain('gerado a partir de')
        ->and($html)->not->toContain('bdg neu');
});

it('o fechamento fica sem muro quando nenhuma fonte trouxe o núcleo do github', function (): void {
    $sources = [new SourceResult('discord', 'Discord', new HeadlineMetrics(), [])];

    $html = Blade::render(
        '<x-portal::retro.slides.closing :sources="$sources" :since="$since" :until="$until" />',
        ['sources' => $sources, 'since' => CarbonImmutable::parse('2026-06-01'), 'until' => CarbonImmutable::parse('2026-06-30')],
    );

    expect($html)->not->toContain('closing-wall')
        ->and($html)->toContain('31/05/2026');
});

it('o painel de conversas abre com um snapshot congelado antes dos campos novos', function (): void {
    $html = view('portal::retro.slides.discord.messages', [
        'total' => 120,
        'with_reactions' => 30,
        'pinned' => 2,
        'chatters' => [['name' => 'Alice', 'messages' => 50]],
    ])->render();

    expect($html)->toContain('vb-totals')
        ->and($html)->toContain('<b>120</b>')
        ->and($html)->toContain('Alice')
        ->and($html)->not->toContain('Pessoas no papo')
        ->and($html)->not->toContain('Pico ·')
        ->and($html)->not->toContain('vb-bars');
});

it('o painel de conversas completo mostra totais, ritmo da semana, ranking e horas', function (): void {
    $hours = array_map(fn (int $hour): array => ['hour' => $hour, 'messages' => $hour === 21 ? 40 : 1], range(0, 23));
    $days = array_map(fn (int $weekday): array => ['weekday' => $weekday, 'messages' => $weekday * 10], range(1, 7));

    $html = view('portal::retro.slides.discord.messages', [
        'total' => 1_200,
        'with_reactions' => 300,
        'pinned' => 4,
        'people' => 85,
        'peak' => ['date' => '04/06', 'messages' => 210],
        'chatters' => [['name' => 'Alice', 'messages' => 50, 'xp' => 300, 'reactions' => 12]],
        'days' => $days,
        'hours' => $hours,
    ])->render();

    expect($html)->toContain('Pessoas no papo')
        ->and($html)->toContain('Pico · 04/06')
        ->and($html)->toContain('O ritmo da semana')
        ->and($html)->toContain('Domingo')
        ->and($html)->toContain('Quem segurou o papo')
        ->and($html)->toContain('pico 21h · 40')
        ->and(mb_substr_count($html, 'class="vb-bar"'))->toBe(23)
        ->and(mb_substr_count($html, 'vb-bar is-peak'))->toBe(1);
});
