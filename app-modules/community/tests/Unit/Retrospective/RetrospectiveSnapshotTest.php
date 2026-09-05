<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use He4rt\Community\Retrospective\DTOs\HeadlineMetrics;
use He4rt\Community\Retrospective\DTOs\Metric;
use He4rt\Community\Retrospective\DTOs\PromotionCard;
use He4rt\Community\Retrospective\DTOs\RetrospectiveSnapshot;
use He4rt\Community\Retrospective\DTOs\SourceFilters;
use He4rt\Community\Retrospective\DTOs\SourceResult;
use He4rt\Community\Retrospective\Enums\PromotionStage;
use He4rt\Community\Retrospective\Slides\FrozenSlide;

it('faz round-trip e reidrata slides como FrozenSlide', function (): void {
    $snapshot = new RetrospectiveSnapshot([
        new SourceResult(
            key: 'github',
            label: 'GitHub',
            headline: new HeadlineMetrics([new Metric('PRs', 3)]),
            slides: [new FrozenSlide('github.panorama', ['meta' => ['prs' => 3]])],
        ),
    ]);

    $restored = RetrospectiveSnapshot::makeFromPayload($snapshot->toArray());

    expect($restored->sources)->toHaveCount(1);

    $source = $restored->sources[0];

    expect($source->key)->toBe('github')
        ->and($source->label)->toBe('GitHub')
        ->and($source->headline->metrics[0]->label)->toBe('PRs')
        ->and($source->headline->metrics[0]->value)->toBe(3)
        ->and($source->slides[0])->toBeInstanceOf(FrozenSlide::class)
        ->and($source->slides[0]->kind())->toBe('github.panorama')
        ->and($source->slides[0]->toArray())->toBe(['meta' => ['prs' => 3]]);
});

it('trata payload vazio como snapshot vazio', function (): void {
    expect(RetrospectiveSnapshot::makeFromPayload([])->isEmpty())->toBeTrue()
        ->and(RetrospectiveSnapshot::makeFromPayload(['sources' => []])->isEmpty())->toBeTrue();
});

it('ignora fontes e métricas malformadas ao reidratar', function (): void {
    $restored = RetrospectiveSnapshot::makeFromPayload([
        'sources' => [
            'nope',
            [
                'key' => 'discord',
                'label' => 'Discord',
                'headline' => ['metrics' => [['label' => 'Msgs', 'value' => 5], ['sem' => 'shape']]],
                'slides' => ['descartado', ['kind' => 'discord.messages', 'props' => ['n' => 5]]],
            ],
        ],
    ]);

    expect($restored->sources)->toHaveCount(1)
        ->and($restored->sources[0]->headline->metrics)->toHaveCount(1)
        ->and($restored->sources[0]->slides)->toHaveCount(1)
        ->and($restored->sources[0]->slides[0]->kind())->toBe('discord.messages');
});

it('lembra os filtros com que foi compilado, para o painel detectar drift', function (): void {
    $filters = new SourceFilters(hideBots: false, exclusions: ['pr:1', 'actor:maria']);

    $snapshot = new RetrospectiveSnapshot(sources: [], filters: $filters);

    $restored = RetrospectiveSnapshot::makeFromPayload($snapshot->toArray());

    expect($restored->filters->hideBots)->toBeFalse()
        ->and($restored->filters->exclusions)->toBe(['pr:1', 'actor:maria']);
});

it('assume os filtros padrão em snapshot antigo, sem a chave', function (): void {
    // Snapshots congelados antes desta mudança não têm `filters` no jsonb; reidratar
    // não pode explodir nem inventar drift.
    $restored = RetrospectiveSnapshot::makeFromPayload(['sources' => []]);

    expect($restored->filters->hideBots)->toBeTrue()
        ->and($restored->filters->exclusions)->toBeEmpty();
});

it('congela e reidrata o member_since dos cartões da tag', function (): void {
    $card = new PromotionCard(
        userId: 'u1',
        name: 'Fulana',
        username: 'fulana',
        avatar: 'a.png',
        stage: PromotionStage::Promoted,
        memberSince: CarbonImmutable::parse('2020-03-01T00:00:00+00:00'),
    );

    $payload = $card->toArray();
    $hydrated = PromotionCard::makeFromPayload($payload);

    expect($payload['member_since'])->toBe('2020-03-01T00:00:00+00:00')
        ->and($hydrated?->memberSince?->toIso8601String())->toBe('2020-03-01T00:00:00+00:00');
});

it('cartão congelado antes do member_since reidrata sem a data', function (): void {
    $hydrated = PromotionCard::makeFromPayload([
        'user_id' => 'u1',
        'stage' => 'promoted',
    ]);

    expect($hydrated?->memberSince)->toBeNull();
});
