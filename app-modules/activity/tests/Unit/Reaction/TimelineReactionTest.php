<?php

declare(strict_types=1);

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;
use He4rt\Activity\Reaction\Enums\TimelineReaction;

it('tem exatamente os seis casos do conjunto fixo', function (): void {
    expect(TimelineReaction::cases())->toHaveCount(6)
        ->and(TimelineReaction::Like->value)->toBe('like')
        ->and(TimelineReaction::Love->value)->toBe('love')
        ->and(TimelineReaction::Laugh->value)->toBe('laugh')
        ->and(TimelineReaction::Celebrate->value)->toBe('celebrate')
        ->and(TimelineReaction::Fire->value)->toBe('fire')
        ->and(TimelineReaction::Sad->value)->toBe('sad');
});

it('implementa os contratos Filament para todos os casos', function (TimelineReaction $reaction): void {
    expect($reaction)->toBeInstanceOf(HasColor::class)
        ->toBeInstanceOf(HasDescription::class)
        ->toBeInstanceOf(HasLabel::class)
        ->and($reaction->getLabel())->toBeString()->not->toBeEmpty()
        ->and($reaction->getColor())->toBeArray()->not->toBeEmpty()
        ->and($reaction->getDescription())->toBeString()->not->toBeEmpty()
        ->and($reaction->getEmoji())->toBeString()->not->toBeEmpty();
})->with(TimelineReaction::cases());

it('devolve o rótulo pt-BR de cada reação', function (): void {
    expect(TimelineReaction::Like->getLabel())->toBe('Curtir')
        ->and(TimelineReaction::Love->getLabel())->toBe('Amei')
        ->and(TimelineReaction::Laugh->getLabel())->toBe('Haha')
        ->and(TimelineReaction::Celebrate->getLabel())->toBe('Parabéns')
        ->and(TimelineReaction::Fire->getLabel())->toBe('Fogo')
        ->and(TimelineReaction::Sad->getLabel())->toBe('Triste');
});

it('devolve o glifo de cada reação', function (): void {
    expect(TimelineReaction::Like->getEmoji())->toBe('👍')
        ->and(TimelineReaction::Love->getEmoji())->toBe('❤️')
        ->and(TimelineReaction::Laugh->getEmoji())->toBe('😂')
        ->and(TimelineReaction::Celebrate->getEmoji())->toBe('🎉')
        ->and(TimelineReaction::Fire->getEmoji())->toBe('🔥')
        ->and(TimelineReaction::Sad->getEmoji())->toBe('😢');
});

it('não repete cor, rótulo, descrição nem glifo entre os casos', function (): void {
    $cases = TimelineReaction::cases();

    $colors = array_map(fn (TimelineReaction $r): string => serialize($r->getColor()), $cases);
    $labels = array_map(fn (TimelineReaction $r): string => $r->getLabel(), $cases);
    $descriptions = array_map(fn (TimelineReaction $r): string => $r->getDescription(), $cases);
    $emojis = array_map(fn (TimelineReaction $r): string => $r->getEmoji(), $cases);

    expect(array_unique($colors))->toHaveCount(6)
        ->and(array_unique($labels))->toHaveCount(6)
        ->and(array_unique($descriptions))->toHaveCount(6)
        ->and(array_unique($emojis))->toHaveCount(6);
});

it('devolve null para valor fora do conjunto', function (): void {
    expect(TimelineReaction::tryFrom('xpto'))->toBeNull()
        ->and(TimelineReaction::tryFrom(''))->toBeNull()
        ->and(TimelineReaction::tryFrom('👍'))->toBeNull();
});

it('lista os seis values em stringifyCases', function (): void {
    expect(TimelineReaction::stringifyCases())
        ->toBe('Available enum cases: like, love, laugh, celebrate, fire, sad');
});
