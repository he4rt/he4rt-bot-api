<?php

declare(strict_types=1);

use He4rt\Identity\User\Models\User;

test('super admin passes any gate ability', function (): void {
    $user = User::factory()->superAdmin()->create();

    expect($user->isSuperAdmin())->toBeTrue()
        ->and($user->can('anything-at-all'))->toBeTrue();
});

test('user without role does not pass undefined abilities', function (): void {
    $user = User::factory()->create();

    expect($user->isSuperAdmin())->toBeFalse()
        ->and($user->can('anything-at-all'))->toBeFalse();
});
