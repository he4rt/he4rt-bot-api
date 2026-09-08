<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use He4rt\Activity\Tracking\Enums\ActivityType;
use He4rt\Activity\Tracking\Models\Interaction;
use He4rt\Portal\Home\ContributionFeed;
use Illuminate\Support\Facades\Blade;

use function Pest\Laravel\get;

beforeEach(function (): void {
    $this->withoutVite();
});

it('conta contribuições visíveis e as pessoas por trás delas', function (): void {
    Interaction::factory()->count(3)->create();
    Interaction::factory()->hidden()->create();

    $panorama = resolve(ContributionFeed::class)->panorama();

    expect($panorama->total)->toBe(3)
        ->and($panorama->people)->toBe(3)
        ->and($panorama->isEmpty())->toBeFalse();
});

it('compõe a barra do maior tipo para o menor e ignora tipo sem contribuição', function (): void {
    Interaction::factory()->count(2)->ofType(ActivityType::Commit)->create();
    Interaction::factory()->count(5)->ofType(ActivityType::Review)->create();

    $composition = resolve(ContributionFeed::class)->panorama()->composition;

    expect($composition)->toHaveCount(2)
        ->and($composition[0]->label)->toBe(ActivityType::Review->getLabel())
        ->and($composition[0]->count)->toBe(5)
        ->and($composition[0]->share)->toBe(round(5 / 7 * 100, 2))
        ->and($composition[1]->count)->toBe(2);
});

it('devolve doze meses e preenche os vazios', function (): void {
    Interaction::factory()->create(['occurred_at' => CarbonImmutable::now()]);

    $timeline = resolve(ContributionFeed::class)->panorama()->timeline;

    expect($timeline)->toHaveCount(12)
        ->and(end($timeline)->count)->toBe(1)
        ->and($timeline[0]->count)->toBe(0)
        // O mês vazio guarda um traço mínimo em vez de sumir da série.
        ->and($timeline[0]->height)->toBe(2.0);
});

it('deixa a contribuição de fora quando ela cai antes da janela', function (): void {
    Interaction::factory()->create(['occurred_at' => CarbonImmutable::now()->subMonths(13)]);

    $panorama = resolve(ContributionFeed::class)->panorama();

    expect($panorama->total)->toBe(1)
        ->and(array_sum(array_column($panorama->timeline, 'count')))->toBe(0);
});

it('reconhece o panorama vazio, que é o que apaga a seção', function (): void {
    expect(resolve(ContributionFeed::class)->panorama()->isEmpty())->toBeTrue();
});

it('renderiza as duas visualizações a partir do panorama', function (): void {
    Interaction::factory()->count(4)->ofType(ActivityType::PrMerged)->create();

    $html = Blade::render(
        '<x-portal::sections.contributions :panorama="$panorama" />',
        ['panorama' => resolve(ContributionFeed::class)->panorama()],
    );

    // O headline quebra o título em spans para destacar a keyword, então a
    // asserção fica no que sai inteiro.
    expect($html)->toContain('id="contribuicoes"')
        ->toContain('Construído em aberto')
        ->toContain('contribuições registradas')
        ->toContain('cp-segment')
        ->toContain('cp-bar-fill');
});

it('não monta a seção na home enquanto o desenho não é aprovado', function (): void {
    Interaction::factory()->count(4)->ofType(ActivityType::PrMerged)->create();

    get('/')->assertOk()->assertDontSee('id="contribuicoes"', escape: false);
});
