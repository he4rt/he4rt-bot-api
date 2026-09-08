<?php

declare(strict_types=1);

use Filament\Support\Icons\Heroicon;
use He4rt\Identity\Authorization\Enums\UserRole;

test('every role has label, color, description and icon', function (UserRole $role): void {
    expect($role->getLabel())->not->toBeEmpty()
        ->and($role->getColor())->not->toBeEmpty()
        ->and($role->getDescription())->not->toBeEmpty()
        ->and($role->getIcon())->toBeInstanceOf(Heroicon::class);
})->with(UserRole::cases());

test('super admin is the red apex of the scale', function (): void {
    expect(UserRole::SuperAdmin->value)->toBe('super-admin')
        ->and(UserRole::GUARD)->toBe('web');
});
