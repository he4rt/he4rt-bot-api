<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use He4rt\Identity\User\Models\User;

test('unauthenticated user is redirected to login', function (): void {
    $this
        ->get('/admin')
        ->assertRedirect('/admin/login');
});

test('admin login page renders', function (): void {
    $this
        ->get('/admin/login')
        ->assertOk();
});

test('super admin can access admin panel', function (): void {
    $user = User::factory()->superAdmin()->create();

    $this
        ->actingAs($user)
        ->get('/admin')
        ->assertOk();
});

test('super admin can access panel via canAccessPanel in production', function (): void {
    $user = User::factory()->superAdmin()->create();

    app()->detectEnvironment(fn () => 'production');

    $panel = Filament::getPanel('admin');

    expect($user->canAccessPanel($panel))->toBeTrue();
});

test('user without role cannot access admin panel in production', function (): void {
    $user = User::factory()->create();

    app()->detectEnvironment(fn () => 'production');

    $panel = Filament::getPanel('admin');

    expect($user->canAccessPanel($panel))->toBeFalse();
});

test('user without role can access admin panel outside production', function (): void {
    $user = User::factory()->create();

    $panel = Filament::getPanel('admin');

    expect($user->canAccessPanel($panel))->toBeTrue();
});
