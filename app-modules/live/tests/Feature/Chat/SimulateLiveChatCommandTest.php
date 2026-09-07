<?php

declare(strict_types=1);

use He4rt\Activity\Message\Models\Message;
use He4rt\Identity\User\Models\User;
use He4rt\Live\Console\SimulateLiveChatCommand;
use He4rt\Live\Events\ChatMessageSent;
use He4rt\Live\Models\Live;
use Illuminate\Cache\FileStore;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\artisan;

beforeEach(function (): void {
    Event::fake([ChatMessageSent::class]);
});

it('usa um cache store persistente entre processos, não o padrão da aplicação', function (): void {
    expect(SimulateLiveChatCommand::cacheStore()->getStore())->toBeInstanceOf(FileStore::class);
});

it('envia mensagens fake até o limite informado, para uma live no ar', function (): void {
    $live = Live::factory()->onAir()->create();

    artisan('live:simulate-chat', [
        'live' => $live->id,
        '--limit' => 3,
        '--interval-min' => 0,
        '--interval-max' => 0,
    ])->assertSuccessful();

    expect(Message::query()->where('channel_id', $live->id)->count())->toBe(3);
    Event::assertDispatchedTimes(ChatMessageSent::class, 3);
});

it('reusa o mesmo pool de usuários fake entre execuções', function (): void {
    $live = Live::factory()->onAir()->create();

    artisan('live:simulate-chat', [
        'live' => $live->id,
        '--limit' => 5,
        '--interval-min' => 0,
        '--interval-max' => 0,
    ])->assertSuccessful();
    $usersAfterFirstRun = User::query()->where('email', 'like', 'chat-sim-%')->count();

    artisan('live:simulate-chat', [
        'live' => $live->id,
        '--limit' => 5,
        '--interval-min' => 0,
        '--interval-max' => 0,
    ])->assertSuccessful();
    $usersAfterSecondRun = User::query()->where('email', 'like', 'chat-sim-%')->count();

    expect($usersAfterFirstRun)->toBeGreaterThan(0)
        ->and($usersAfterSecondRun)->toBe($usersAfterFirstRun);
});

it('não envia mensagens quando a flag de simulação já está desligada', function (): void {
    $live = Live::factory()->onAir()->create();
    SimulateLiveChatCommand::cacheStore()->put(SimulateLiveChatCommand::cacheKey($live), value: false);

    artisan('live:simulate-chat', [
        'live' => $live->id,
        '--limit' => 3,
        '--interval-min' => 0,
        '--interval-max' => 0,
    ])->assertSuccessful();

    expect(Message::query()->count())->toBe(0);
});
