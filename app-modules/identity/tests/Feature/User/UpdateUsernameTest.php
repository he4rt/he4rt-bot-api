<?php

declare(strict_types=1);

use He4rt\Identity\User\Actions\UpdateUsername;
use He4rt\Identity\User\Exceptions\UsernameException;
use He4rt\Identity\User\Models\User;
use Illuminate\Support\Facades\Date;

beforeEach(function (): void {
    config()->set('he4rt.username_cooldown_days', 7);
    config()->set('app.display_timezone', 'America/Sao_Paulo');
});

test('updates username with lowercase normalization and sets timestamps', function (): void {
    $user = User::factory()->create([
        'username' => 'originaluser',
        'username_manually_set_at' => null,
        'username_updated_at' => null,
    ]);

    $action = resolve(UpdateUsername::class);
    $updated = $action->handle($user, 'NewHandle_99');

    expect($updated->username)->toBe('newhandle_99')
        ->and($updated->username_manually_set_at)->not->toBeNull()
        ->and($updated->username_updated_at)->not->toBeNull();
});

test('preserves username_manually_set_at on subsequent updates after cooldown', function (): void {
    $initialManualDate = now()->subDays(10);
    $user = User::factory()->create([
        'username' => 'firsthandle',
        'username_manually_set_at' => $initialManualDate,
        'username_updated_at' => $initialManualDate,
    ]);

    $action = resolve(UpdateUsername::class);
    $updated = $action->handle($user, 'secondhandle');

    expect($updated->username)->toBe('secondhandle')
        ->and($updated->username_manually_set_at->toIso8601String())->toBe($initialManualDate->toIso8601String())
        ->and($updated->username_updated_at->greaterThan($initialManualDate))->toBeTrue();
});

test('throws exception when cooldown is still active', function (): void {
    Date::setTestNow('2026-09-08 12:00:00');

    $user = User::factory()->create([
        'username' => 'currentuser',
        'username_manually_set_at' => now()->subDays(3),
        'username_updated_at' => now()->subDays(3),
    ]);

    $action = resolve(UpdateUsername::class);

    expect(fn () => $action->handle($user, 'newhandle'))
        ->toThrow(UsernameException::class, '12/09/2026 09:00'); // 2026-09-08 12:00 UTC - 3 days + 7 days = 2026-09-12 12:00 UTC = 09:00 America/Sao_Paulo

    Date::setTestNow();
});

test('allows update after 7 days cooldown has elapsed', function (): void {
    $user = User::factory()->create([
        'username' => 'currentuser',
        'username_manually_set_at' => now()->subDays(8),
        'username_updated_at' => now()->subDays(8),
    ]);

    $action = resolve(UpdateUsername::class);
    $updated = $action->handle($user, 'newhandle');

    expect($updated->username)->toBe('newhandle');
});

test('throws exception when new username is same as current username', function (): void {
    $user = User::factory()->create([
        'username' => 'myhandle',
        'username_manually_set_at' => null,
        'username_updated_at' => null,
    ]);

    $action = resolve(UpdateUsername::class);

    expect(fn () => $action->handle($user, 'MYHANDLE'))
        ->toThrow(UsernameException::class, 'O novo @ deve ser diferente do atual.');

    expect($user->fresh()->username_updated_at)->toBeNull();
});

test('throws exception when username is already taken case-insensitively', function (): void {
    User::factory()->create(['username' => 'takenhandle']);
    $user = User::factory()->create(['username' => 'myhandle']);

    $action = resolve(UpdateUsername::class);

    expect(fn () => $action->handle($user, 'TAKENHANDLE'))
        ->toThrow(UsernameException::class, 'O @takenhandle já está em uso por outro membro.');
});

test('rejects invalid username formats', function (string $invalidUsername): void {
    $user = User::factory()->create(['username' => 'validuser']);
    $action = resolve(UpdateUsername::class);

    expect(fn () => $action->handle($user, $invalidUsername))
        ->toThrow(UsernameException::class);
})->with([
    'single char' => 'a',
    'too long' => str_repeat('a', 33),
    'starts with dot' => '.username',
    'ends with dot' => 'username.',
    'starts with dash' => '-username',
    'ends with dash' => 'username-',
    'starts with underscore' => '_username',
    'ends with underscore' => 'username_',
    'consecutive dots' => 'user..name',
    'consecutive dashes' => 'user--name',
    'consecutive underscores' => 'user__name',
    'consecutive mixed' => 'user.-name',
    'invalid chars space' => 'user name',
    'invalid chars at symbol' => 'user@name',
    'invalid chars exclamation' => 'user!name',
]);

test('rejects reserved system usernames', function (string $reservedName): void {
    $user = User::factory()->create(['username' => 'normaluser']);
    $action = resolve(UpdateUsername::class);

    expect(fn () => $action->handle($user, $reservedName))
        ->toThrow(UsernameException::class, "O @{$reservedName} está reservado para o sistema e não pode ser utilizado.");
})->with([
    'admin',
    'root',
    'he4rt',
    'system',
    'support',
    'api',
]);

test('prevents ordinary users from claiming admin username from config', function (): void {
    config()->set('he4rt.admins', 'adminmaster,he4rtfounder');

    $user = User::factory()->create(['username' => 'regularuser']);
    $action = resolve(UpdateUsername::class);

    expect(fn () => $action->handle($user, 'adminmaster'))
        ->toThrow(UsernameException::class, 'O @adminmaster está reservado para o sistema e não pode ser utilizado.');
});

test('allows admin user to change their username', function (): void {
    config()->set('he4rt.admins', 'danielhe4rt');

    $admin = User::factory()->superAdmin()->create(['username' => 'danielhe4rt']);
    expect($admin->isAdmin())->toBeTrue();

    $action = resolve(UpdateUsername::class);
    $updated = $action->handle($admin, 'daniel.reis');

    expect($updated->username)->toBe('daniel.reis');
});

test('admin is exempt from 7 days cooldown and can change username repeatedly', function (): void {
    config()->set('he4rt.admins', 'danielhe4rt');

    $admin = User::factory()->superAdmin()->create([
        'username' => 'danielhe4rt',
        'username_updated_at' => now()->subMinutes(5),
    ]);

    expect($admin->isAdmin())->toBeTrue();

    $action = resolve(UpdateUsername::class);
    $updated = $action->handle($admin, 'daniel.reis');

    expect($updated->username)->toBe('daniel.reis');
});
