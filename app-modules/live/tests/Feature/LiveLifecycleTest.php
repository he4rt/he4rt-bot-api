<?php

declare(strict_types=1);

use He4rt\Live\Actions\CreateLive;
use He4rt\Live\Actions\EndLive;
use He4rt\Live\Actions\MarkLiveOnline;
use He4rt\Live\Actions\RotateStreamKey;
use He4rt\Live\Enums\LiveStatus;
use He4rt\Live\Events\LiveEnded;
use He4rt\Live\Events\LiveStarted;
use He4rt\Live\Exceptions\CurrentLiveAlreadyExists;
use He4rt\Live\Models\Live;
use Illuminate\Support\Facades\Event;

it('cria uma live com stream key gerada', function (): void {
    $live = resolve(CreateLive::class)->execute('Retrô He4rt', 'Balanço do mês');

    expect($live->status)->toBe(LiveStatus::Created)
        ->and($live->stream_key)->toHaveLength(40)
        ->and($live->peak_viewers)->toBe(0)
        ->and(Live::query()->current()->sole()->id)->toBe($live->id);
});

it('impede duas lives correntes ao mesmo tempo', function (): void {
    resolve(CreateLive::class)->execute('Primeira', description: null);

    resolve(CreateLive::class)->execute('Segunda', description: null);
})->throws(CurrentLiveAlreadyExists::class);

it('permite criar nova live depois de encerrar a anterior', function (): void {
    $first = resolve(CreateLive::class)->execute('Primeira', description: null);
    resolve(EndLive::class)->execute($first);

    $second = resolve(CreateLive::class)->execute('Segunda', description: null);

    expect(Live::query()->current()->sole()->id)->toBe($second->id);
});

it('marca online com started_at apenas na primeira vez e re-emite o evento', function (): void {
    Event::fake([LiveStarted::class]);
    $live = resolve(CreateLive::class)->execute('Retrô', description: null);

    $live = resolve(MarkLiveOnline::class)->execute($live);

    $firstStart = $live->started_at;

    $this->travel(5)->minutes();
    $live = resolve(MarkLiveOnline::class)->execute($live);

    expect($live->status)->toBe(LiveStatus::OnAir)
        ->and($live->started_at?->equalTo($firstStart))->toBeTrue();
    Event::assertDispatchedTimes(LiveStarted::class, 2);
});

it('encerra a live e broadcasta o fim', function (): void {
    Event::fake([LiveEnded::class]);
    $live = resolve(CreateLive::class)->execute('Retrô', description: null);

    $live = resolve(EndLive::class)->execute($live);

    expect($live->status)->toBe(LiveStatus::Ended)
        ->and($live->ended_at)->not->toBeNull();
    Event::assertDispatched(fn (LiveEnded $event): bool => $event->liveId === $live->id);
});

it('rotaciona a stream key', function (): void {
    $live = resolve(CreateLive::class)->execute('Retrô', description: null);
    $original = $live->stream_key;

    $live = resolve(RotateStreamKey::class)->execute($live);

    expect($live->stream_key)->toHaveLength(40)->not->toBe($original);
});

it('monta a chave de stream no formato do OBS', function (): void {
    $live = resolve(CreateLive::class)->execute('Retrô', description: null);

    expect($live->obsStreamKey())->toBe(sprintf('live?user=he4rt&pass=%s', $live->stream_key));
});
