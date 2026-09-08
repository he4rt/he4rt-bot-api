<?php

declare(strict_types=1);

use Filament\Support\Icons\Heroicon;
use He4rt\Community\Retrospective\Enums\RetrospectiveStatus;

it('implementa os contratos Filament para todos os casos', function (RetrospectiveStatus $status): void {
    expect($status->getLabel())->toBeString()->not->toBeEmpty()
        ->and($status->getColor())->toBeString()->not->toBeEmpty()
        ->and($status->getDescription())->toBeString()->not->toBeEmpty()
        ->and($status->getIcon())->toBeInstanceOf(Heroicon::class);
})->with(RetrospectiveStatus::cases());

it('expõe os atalhos de estado', function (): void {
    expect(RetrospectiveStatus::Draft->isDraft())->toBeTrue()
        ->and(RetrospectiveStatus::Draft->isPublished())->toBeFalse()
        ->and(RetrospectiveStatus::Published->isPublished())->toBeTrue()
        ->and(RetrospectiveStatus::Publishing->isPublished())->toBeFalse();
});
