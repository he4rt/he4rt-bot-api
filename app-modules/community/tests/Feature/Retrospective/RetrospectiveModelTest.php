<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use He4rt\Community\Retrospective\DTOs\DeckConfig;
use He4rt\Community\Retrospective\DTOs\HeadlineMetrics;
use He4rt\Community\Retrospective\DTOs\Metric;
use He4rt\Community\Retrospective\DTOs\PromotionCard;
use He4rt\Community\Retrospective\DTOs\PromotionEntry;
use He4rt\Community\Retrospective\DTOs\RetrospectiveSnapshot;
use He4rt\Community\Retrospective\DTOs\SourceFilters;
use He4rt\Community\Retrospective\DTOs\SourceResult;
use He4rt\Community\Retrospective\Enums\CoverKind;
use He4rt\Community\Retrospective\Enums\PromotionStage;
use He4rt\Community\Retrospective\Enums\RetrospectiveStatus;
use He4rt\Community\Retrospective\Models\Retrospective;
use He4rt\Community\Retrospective\Slides\FrozenSlide;

it('persiste e reidrata deck_config e snapshot como VOs tipados', function (): void {
    $config = new DeckConfig(
        order: ['github'],
        hiddenSources: ['discord'],
        exclusions: ['github' => ['pr:1']],
    );

    $snapshot = new RetrospectiveSnapshot([
        new SourceResult('github', 'GitHub', new HeadlineMetrics([new Metric('PRs', 3)]), [
            new FrozenSlide('github.panorama', ['meta' => ['prs' => 3]]),
        ]),
    ]);

    $retrospective = Retrospective::factory()->create([
        'deck_config' => $config,
        'snapshot' => $snapshot,
    ]);

    $fresh = $retrospective->fresh();

    expect($fresh->deck_config)->toBeInstanceOf(DeckConfig::class)
        ->and($fresh->deck_config->order)->toBe(['github'])
        ->and($fresh->deck_config->allExclusions())->toBe(['pr:1'])
        ->and($fresh->snapshot)->toBeInstanceOf(RetrospectiveSnapshot::class)
        ->and($fresh->snapshot->sources[0]->slides[0])->toBeInstanceOf(FrozenSlide::class)
        ->and($fresh->snapshot->sources[0]->slides[0]->kind())->toBe('github.panorama');
});

it('deixa o snapshot nulo enquanto rascunho, com deck_config sempre presente', function (): void {
    $retrospective = Retrospective::factory()->create()->fresh();

    expect($retrospective->snapshot)->toBeNull()
        ->and($retrospective->status)->toBe(RetrospectiveStatus::Draft)
        ->and($retrospective->deck_config)->toBeInstanceOf(DeckConfig::class);
});

it('projeta period e filters a partir das colunas', function (): void {
    $retrospective = Retrospective::factory()->create([
        'since' => CarbonImmutable::parse('2026-06-01 00:00:00'),
        'until' => CarbonImmutable::parse('2026-06-30 23:59:59'),
        'hide_bots' => false,
        'deck_config' => new DeckConfig(exclusions: ['github' => ['pr:9']]),
    ]);

    expect($retrospective->period()->since->toDateString())->toBe('2026-06-01')
        ->and($retrospective->period()->until->toDateString())->toBe('2026-06-30')
        ->and($retrospective->filters()->hideBots)->toBeFalse()
        ->and($retrospective->filters()->exclusions)->toBe(['pr:9']);
});

it('scopePublished traz só as publicadas', function (): void {
    Retrospective::factory()->create();
    $published = Retrospective::factory()->published()->create();

    expect(Retrospective::query()->published()->pluck('id')->all())->toBe([$published->id]);
});

it('não pede republicação enquanto é rascunho', function (): void {
    $retrospective = Retrospective::factory()->create([
        'deck_config' => new DeckConfig(exclusions: ['github' => ['pr:1']]),
    ]);

    expect($retrospective->needsRepublish())->toBeFalse();
});

it('não pede republicação quando os filtros batem com o snapshot congelado', function (): void {
    $retrospective = Retrospective::factory()
        ->published(new RetrospectiveSnapshot(
            sources: [],
            filters: new SourceFilters(hideBots: true, exclusions: ['pr:1']),
        ))
        ->create([
            'hide_bots' => true,
            'deck_config' => new DeckConfig(exclusions: ['github' => ['pr:1']]),
        ]);

    expect($retrospective->needsRepublish())->toBeFalse();
});

it('pede republicação quando a exclusion mudou depois de publicar', function (): void {
    $retrospective = Retrospective::factory()
        ->published(new RetrospectiveSnapshot(
            sources: [],
            filters: new SourceFilters(hideBots: true, exclusions: ['pr:1']),
        ))
        ->create([
            'hide_bots' => true,
            'deck_config' => new DeckConfig(exclusions: ['github' => ['pr:1', 'pr:2']]),
        ]);

    expect($retrospective->needsRepublish())->toBeTrue();
});

it('pede republicação quando ocultar bots mudou depois de publicar', function (): void {
    $retrospective = Retrospective::factory()
        ->published(new RetrospectiveSnapshot(
            sources: [],
            filters: new SourceFilters(hideBots: true),
        ))
        ->create(['hide_bots' => false, 'deck_config' => new DeckConfig()]);

    expect($retrospective->needsRepublish())->toBeTrue();
});

it('ignora ordem e on/off: esses re-derivam sem republicar', function (): void {
    $retrospective = Retrospective::factory()
        ->published(new RetrospectiveSnapshot(sources: [], filters: new SourceFilters(hideBots: true)))
        ->create([
            'hide_bots' => true,
            'deck_config' => new DeckConfig(
                order: ['discord', 'github'],
                hiddenSources: ['discord'],
                hiddenSlides: ['github.repos'],
            ),
        ]);

    expect($retrospective->needsRepublish())->toBeFalse();
});

it('pede republicação quando a lista da tag muda depois de publicar', function (): void {
    $card = new PromotionCard(
        userId: 'u1',
        name: 'Fulana',
        username: 'fulana',
        avatar: 'a.png',
        stage: PromotionStage::Promoted,
        reason: 'segurou o #ajuda',
    );

    $retrospective = Retrospective::factory()
        ->published(new RetrospectiveSnapshot(promotions: [$card]))
        ->create([
            'deck_config' => new DeckConfig(promotions: [
                new PromotionEntry('u1', PromotionStage::Promoted, 'segurou o #ajuda'),
            ]),
        ]);

    expect($retrospective->needsRepublish())->toBeFalse();

    // Trocar a pessoa muda número exibido: é dado, não apresentação.
    $retrospective->update([
        'deck_config' => $retrospective->deck_config->withPromotionsFor(
            PromotionStage::Promoted,
            [new PromotionEntry('u2', PromotionStage::Promoted, 'segurou o #ajuda')],
        ),
    ]);

    expect($retrospective->fresh()->needsRepublish())->toBeTrue();
});

it('corrigir o motivo também deixa o publicado defasado', function (): void {
    $retrospective = Retrospective::factory()
        ->published(new RetrospectiveSnapshot(promotions: [
            new PromotionCard('u1', 'Fulana', 'fulana', 'a.png', PromotionStage::Promoted, 'motivo antigo'),
        ]))
        ->create([
            'deck_config' => new DeckConfig(promotions: [
                new PromotionEntry('u1', PromotionStage::Promoted, 'motivo novo'),
            ]),
        ]);

    expect($retrospective->needsRepublish())->toBeTrue();
});

it('ordem e on/off de slide continuam sem pedir republicação', function (): void {
    $retrospective = Retrospective::factory()
        ->published(new RetrospectiveSnapshot())
        ->create(['deck_config' => new DeckConfig()]);

    $retrospective->update([
        'deck_config' => $retrospective->deck_config
            ->withOrder(['discord', 'github'])
            ->withSlideVisible('he4rt.tag', visible: false),
    ]);

    expect($retrospective->fresh()->needsRepublish())->toBeFalse();
});

it('numera a edição de onboarding pela ordem do início do período', function (): void {
    $may = Retrospective::factory()->onboarding()->create(['since' => CarbonImmutable::parse('2026-05-01')]);
    $july = Retrospective::factory()->onboarding()->create(['since' => CarbonImmutable::parse('2026-07-01')]);
    $june = Retrospective::factory()->onboarding()->create(['since' => CarbonImmutable::parse('2026-06-01')]);
    // Retrospectiva comum no meio: não conta como edição do onboarding.
    Retrospective::factory()->create(['since' => CarbonImmutable::parse('2026-05-15')]);

    expect($may->editionNumber())->toBe(1)
        ->and($june->editionNumber())->toBe(2)
        ->and($july->editionNumber())->toBe(3);
});

it('abre como retrospectiva por padrão e persiste o tipo de capa como enum', function (): void {
    $default = Retrospective::factory()->create();
    $onboarding = Retrospective::factory()->onboarding()->create();

    expect($default->fresh()->cover_kind)->toBe(CoverKind::Retrospective)
        ->and($default->fresh()->deck_config->hosts)->toBeEmpty()
        ->and($onboarding->fresh()->cover_kind)->toBe(CoverKind::Onboarding);
});
