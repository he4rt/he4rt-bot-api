<?php

declare(strict_types=1);

use He4rt\Community\Retrospective\DTOs\DeckConfig;
use He4rt\PanelAdmin\Filament\Resources\Retrospectives\Support\DeckStructure;
use He4rt\PanelAdmin\Filament\Resources\Retrospectives\Support\SourceBlock;
use Tests\Support\Retrospective\PlainRetrospectiveSource;

it('respeita a ordem curada e joga fontes fora da lista para o fim', function (): void {
    $blocks = DeckStructure::blocks(new DeckConfig(order: ['discord']));

    $keys = array_map(static fn (SourceBlock $block): string => $block->key, $blocks);

    expect($keys[0])->toBe('discord')
        ->and($keys)->toContain('github')
        ->and(array_search('github', $keys, strict: true))->toBeGreaterThan(0);
});

it('projeta on/off de fonte e de slide a partir do deck_config', function (): void {
    $blocks = DeckStructure::blocks(new DeckConfig(
        order: ['github'],
        hiddenSources: ['github'],
        hiddenSlides: ['github.repos'],
    ));

    $github = collect($blocks)->firstOrFail(fn (SourceBlock $block): bool => $block->key === 'github');

    expect($github->visible)->toBeFalse()
        ->and(collect($github->slides)->firstOrFail(fn ($slide): bool => $slide->kind === 'github.repos')->visible)->toBeFalse()
        ->and(collect($github->slides)->firstOrFail(fn ($slide): bool => $slide->kind === 'github.panorama')->visible)->toBeTrue();
});

it('entrega o catálogo de slides da fonte que cura', function (): void {
    $blocks = DeckStructure::blocks(new DeckConfig());

    $github = collect($blocks)->firstOrFail(fn (SourceBlock $block): bool => $block->key === 'github');

    expect($github->curatable)->toBeTrue()
        ->and($github->slides)->not->toBeEmpty()
        ->and(collect($github->slides)->pluck('kind')->all())->toContain('github.repos');
});

it('aceita fonte que não cura, sem catálogo de slides', function (): void {
    PlainRetrospectiveSource::register();

    $blocks = DeckStructure::blocks(new DeckConfig());

    $plain = collect($blocks)->firstOrFail(fn (SourceBlock $block): bool => $block->key === PlainRetrospectiveSource::KEY);

    expect($plain->label)->toBe(PlainRetrospectiveSource::LABEL)
        ->and($plain->curatable)->toBeFalse()
        ->and($plain->slides)->toBeEmpty()
        ->and($plain->visible)->toBeTrue();
});

it('desloca uma fonte e devolve a ordem inteira, ancorando as demais', function (): void {
    $blocks = DeckStructure::blocks(new DeckConfig(order: ['github', 'discord']));

    expect(DeckStructure::moved($blocks, 'discord', offset: -1))->toBe(['discord', 'github'])
        ->and(DeckStructure::moved($blocks, 'github', offset: 1))->toBe(['discord', 'github']);
});

it('ignora deslocamento que sairia das pontas', function (): void {
    $blocks = DeckStructure::blocks(new DeckConfig(order: ['github', 'discord']));

    expect(DeckStructure::moved($blocks, 'github', offset: -1))->toBe(['github', 'discord'])
        ->and(DeckStructure::moved($blocks, 'discord', offset: 1))->toBe(['github', 'discord'])
        ->and(DeckStructure::moved($blocks, 'inexistente', offset: -1))->toBe(['github', 'discord']);
});
