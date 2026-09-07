<?php

declare(strict_types=1);

use He4rt\Activity\Message\Models\Message;
use He4rt\Identity\User\Models\User;
use He4rt\Live\Chat\Actions\SendChatMessage;
use He4rt\Live\Events\ChatMessageSent;
use He4rt\Live\Models\Live;
use He4rt\Portal\Live\LiveChat;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

it('lista o histórico da live', function (): void {
    $live = Live::factory()->onAir()->create();
    $user = User::factory()->create();
    resolve(SendChatMessage::class)->execute($user, $live, 'primeira mensagem');

    livewire(LiveChat::class, ['liveId' => $live->id])
        ->assertSee('primeira mensagem')
        ->assertSee($user->username);
});

it('não mostra o form para visitante deslogado', function (): void {
    $live = Live::factory()->onAir()->create();

    livewire(LiveChat::class, ['liveId' => $live->id])
        ->assertSee('Entre para participar do chat')
        ->assertDontSeeHtml('wire:submit="send"');
});

it('usuário logado envia mensagem', function (): void {
    Event::fake([ChatMessageSent::class]);
    $live = Live::factory()->onAir()->create();
    $user = User::factory()->create();

    actingAs($user);

    livewire(LiveChat::class, ['liveId' => $live->id])
        ->set('draft', 'salve!')
        ->call('send')
        ->assertHasNoErrors()
        ->assertSet('draft', '');

    expect(Message::query()->where('content', 'salve!')->exists())->toBeTrue();
});

it('valida o tamanho da mensagem', function (): void {
    $live = Live::factory()->onAir()->create();
    $user = User::factory()->create();

    actingAs($user);

    livewire(LiveChat::class, ['liveId' => $live->id])
        ->set('draft', str_repeat('a', 501))
        ->call('send')
        ->assertHasErrors(['draft' => 'max']);
});

it('exibe erro amigável quando o domínio rejeita', function (): void {
    $live = Live::factory()->onAir()->create();
    $user = User::factory()->create(['banned_at' => now()]);

    actingAs($user);

    livewire(LiveChat::class, ['liveId' => $live->id])
        ->set('draft', 'oi')
        ->call('send')
        ->assertHasErrors('draft');
});
