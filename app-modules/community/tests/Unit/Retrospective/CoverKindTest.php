<?php

declare(strict_types=1);

use Filament\Support\Icons\Heroicon;
use He4rt\Community\Retrospective\Enums\CoverKind;

it('implementa os contratos Filament para todos os casos', function (CoverKind $kind): void {
    expect($kind->getLabel())->toBeString()->not->toBeEmpty()
        ->and($kind->getColor())->toBeString()->not->toBeEmpty()
        ->and($kind->getDescription())->toBeString()->not->toBeEmpty()
        ->and($kind->getIcon())->toBeInstanceOf(Heroicon::class);
})->with(CoverKind::cases());

it('só o onboarding se declara onboarding', function (): void {
    expect(CoverKind::Onboarding->isOnboarding())->toBeTrue()
        ->and(CoverKind::Retrospective->isOnboarding())->toBeFalse();
});
