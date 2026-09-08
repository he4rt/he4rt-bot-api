<?php

declare(strict_types=1);

use He4rt\Community\Retrospective\Actions\ComposeDeck;
use He4rt\Community\Retrospective\Contracts\Slide;
use He4rt\Community\Retrospective\DTOs\DeckConfig;
use He4rt\Community\Retrospective\DTOs\HeadlineMetrics;
use He4rt\Community\Retrospective\DTOs\Metric;
use He4rt\Community\Retrospective\DTOs\RetrospectiveSnapshot;
use He4rt\Community\Retrospective\DTOs\SourceResult;
use He4rt\Community\Retrospective\Slides\FrozenSlide;

function retroSnapshot(): RetrospectiveSnapshot
{
    return new RetrospectiveSnapshot([
        new SourceResult('discord', 'Discord', new HeadlineMetrics([new Metric('Msgs', 5)]), [
            new FrozenSlide('discord.messages', ['n' => 5]),
        ]),
        new SourceResult('github', 'GitHub', new HeadlineMetrics([new Metric('PRs', 3)]), [
            new FrozenSlide('github.panorama', ['meta' => []]),
            new FrozenSlide('github.repos', ['repos' => []]),
        ]),
    ]);
}

it('aplica a ordem editorial do deck_config', function (): void {
    $deck = new ComposeDeck()->execute(retroSnapshot(), new DeckConfig(order: ['github', 'discord']));

    expect(array_map(fn (SourceResult $source): string => $source->key, $deck))->toBe(['github', 'discord']);
});

it('oculta um slide por kind mantendo o resto da fonte', function (): void {
    $deck = new ComposeDeck()->execute(retroSnapshot(), new DeckConfig(
        order: ['github', 'discord'],
        hiddenSlides: ['github.repos'],
    ));

    $githubKinds = array_map(fn (Slide $slide): string => $slide->kind(), $deck[0]->slides);

    expect($githubKinds)->toBe(['github.panorama']);
});

it('remove a fonte inteira quando desligada', function (): void {
    $deck = new ComposeDeck()->execute(retroSnapshot(), new DeckConfig(hiddenSources: ['discord']));

    expect(array_map(fn (SourceResult $source): string => $source->key, $deck))->toBe(['github']);
});

it('mantém a fonte com chips mesmo se todos os slides forem ocultados', function (): void {
    $deck = new ComposeDeck()->execute(retroSnapshot(), new DeckConfig(
        hiddenSlides: ['discord.messages', 'github.panorama', 'github.repos'],
    ));

    expect($deck)->toHaveCount(2)
        ->and($deck[0]->slides)->toBeEmpty();
});
