<?php

declare(strict_types=1);

use He4rt\Live\Actions\CheckLiveStatusAction;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

it('reporta live no ar quando o path está ready', function (): void {
    Http::fake(['localhost:9997/*' => Http::response(['ready' => true, 'readyTime' => '2026-08-29T20:00:00Z'])]);

    $status = resolve(CheckLiveStatusAction::class)->execute();

    expect($status->onAir)->toBeTrue()
        ->and($status->startedAt?->toIso8601ZuluString())->toBe('2026-08-29T20:00:00Z');
});

it('reporta offline quando o path existe mas não está ready', function (): void {
    Http::fake(['localhost:9997/*' => Http::response(['ready' => false, 'readyTime' => null])]);

    expect(resolve(CheckLiveStatusAction::class)->execute()->onAir)->toBeFalse();
});

it('reporta offline quando a Control API responde 404', function (): void {
    Http::fake(['localhost:9997/*' => Http::response(status: 404)]);

    expect(resolve(CheckLiveStatusAction::class)->execute()->onAir)->toBeFalse();
});

it('reporta offline quando a Control API está fora do ar', function (): void {
    Http::fake(fn () => throw new ConnectionException('connection refused'));

    expect(resolve(CheckLiveStatusAction::class)->execute()->onAir)->toBeFalse();
});

it('mantém no ar com startedAt nulo quando readyTime é inválido', function (): void {
    Http::fake(['localhost:9997/*' => Http::response(['ready' => true, 'readyTime' => 'not-a-date'])]);

    $status = resolve(CheckLiveStatusAction::class)->execute();

    expect($status->onAir)->toBeTrue()
        ->and($status->startedAt)->toBeNull();
});

it('cacheia o status por alguns segundos', function (): void {
    Http::fake(['localhost:9997/*' => Http::response(['ready' => true, 'readyTime' => '2026-08-29T20:00:00Z'])]);

    resolve(CheckLiveStatusAction::class)->execute();
    resolve(CheckLiveStatusAction::class)->execute();

    Http::assertSentCount(1);
});
