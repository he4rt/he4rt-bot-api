<?php

declare(strict_types=1);

use Carbon\CarbonInterface;
use He4rt\Identity\User\Enums\UserSituation;
use He4rt\Identity\User\Models\User;

/**
 * `situation` é derivado de duas colunas já carregadas: não há consulta por
 * trás. Os modelos aqui são instanciados sem persistir, porque a suíte Unit
 * roda sem banco (só a Feature recebe LazilyRefreshDatabase).
 */
function userWith(?CarbonInterface $bannedAt = null, ?CarbonInterface $suspendedUntil = null): User
{
    $user = new User();

    $user->banned_at = $bannedAt;
    $user->suspended_until = $suspendedUntil;

    return $user;
}

test('banned_at preenchido devolve Banned', function (): void {
    expect(userWith(bannedAt: now()->subDay())->situation)->toBe(UserSituation::Banned);
});

test('suspensão vigente devolve Suspended', function (): void {
    expect(userWith(suspendedUntil: now()->addWeek())->situation)->toBe(UserSituation::Suspended);
});

test('suspensão vencida devolve Active', function (): void {
    expect(userWith(suspendedUntil: now()->subDay())->situation)->toBe(UserSituation::Active);
});

test('sem punição devolve Active', function (): void {
    expect(userWith()->situation)->toBe(UserSituation::Active);
});

test('banimento vence suspensão quando os dois estão preenchidos', function (): void {
    $user = userWith(bannedAt: now()->subDay(), suspendedUntil: now()->addWeek());

    expect($user->situation)->toBe(UserSituation::Banned);
});
