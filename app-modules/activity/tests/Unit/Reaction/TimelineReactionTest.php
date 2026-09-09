<?php

declare(strict_types=1);

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

it('implementa o contrato HasLabel', function (TimelineReaction $reaction): void {
    expect($reaction)->toBeInstanceOf(HasLabel::class)
        ->and($reaction->getLabel())->toBeString()->not->toBeEmpty()
        ->and($reaction->emoji())->toBeString()->not->toBeEmpty();
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
    expect(TimelineReaction::Like->emoji())->toBe('👍')
        ->and(TimelineReaction::Love->emoji())->toBe('❤️')
        ->and(TimelineReaction::Laugh->emoji())->toBe('😂')
        ->and(TimelineReaction::Celebrate->emoji())->toBe('🎉')
        ->and(TimelineReaction::Fire->emoji())->toBe('🔥')
        ->and(TimelineReaction::Sad->emoji())->toBe('😢');
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
