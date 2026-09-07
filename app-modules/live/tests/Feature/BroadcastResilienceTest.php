<?php

declare(strict_types=1);

use He4rt\Activity\Message\Models\Message;
use He4rt\Identity\User\Models\User;
use He4rt\Live\Actions\MarkLiveOnline;
use He4rt\Live\Chat\Actions\SendChatMessage;
use He4rt\Live\Enums\LiveStatus;
use He4rt\Live\Models\Live;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Contracts\Broadcasting\Broadcaster;
use Illuminate\Support\Facades\Broadcast;

/** Simula o broadcaster indisponível (ex.: Reverb fora do ar) para as ações usarem rescue(). */
beforeEach(function (): void {
    Broadcast::extend('exploding', fn (): Broadcaster => new class implements Broadcaster
    {
        public function auth($request): void {}

        public function validAuthenticationResponse($request, $result): void {}

        public function broadcast(array $channels, $event, array $payload = []): void
        {
            throw new BroadcastException('Reverb indisponível.');
        }
    });

    config([
        'broadcasting.default' => 'exploding',
        'broadcasting.connections.exploding' => ['driver' => 'exploding'],
    ]);
});

it('persiste o status OnAir e o started_at mesmo com o broadcast falhando', function (): void {
    $live = Live::factory()->create();

    $result = resolve(MarkLiveOnline::class)->execute($live);

    expect($result->status)->toBe(LiveStatus::OnAir)
        ->and($result->started_at)->not->toBeNull();
    expect(Live::query()->find($live->id)->status)->toBe(LiveStatus::OnAir);
});

it('persiste a mensagem do chat mesmo com o broadcast falhando', function (): void {
    $user = User::factory()->create();
    $live = Live::factory()->onAir()->create();

    $data = resolve(SendChatMessage::class)->execute($user, $live, 'salve chat!');

    expect($data->content)->toBe('salve chat!')
        ->and(Message::query()->where('channel_id', $live->id)->sole()->content)->toBe('salve chat!');
});
