<?php

declare(strict_types=1);

use He4rt\Identity\Authorization\Enums\UserRole;
use He4rt\Identity\User\Models\User;

use function Pest\Laravel\artisan;

test('grants the super admin role by username', function (): void {
    $user = User::factory()->create(['username' => 'danielhe4rt']);

    artisan('identity:grant-super-admin', ['username' => 'danielhe4rt'])
        ->assertSuccessful();

    expect($user->fresh()?->hasRole(UserRole::SuperAdmin))->toBeTrue();
});

test('running twice does not duplicate the assignment', function (): void {
    User::factory()->create(['username' => 'danielhe4rt']);

    artisan('identity:grant-super-admin', ['username' => 'danielhe4rt'])->assertSuccessful();
    artisan('identity:grant-super-admin', ['username' => 'danielhe4rt'])->assertSuccessful();

    $this->assertDatabaseCount('model_has_roles', 1);
});

test('fails when the username does not exist', function (): void {
    artisan('identity:grant-super-admin', ['username' => 'ninguem'])
        ->assertFailed();
});
