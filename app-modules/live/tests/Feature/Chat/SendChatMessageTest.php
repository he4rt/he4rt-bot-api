<?php

declare(strict_types=1);

use He4rt\Activity\Message\Enums\MessageKind;
use He4rt\Activity\Message\Enums\MessageSourceKind;
use He4rt\Activity\Message\Models\Message;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use He4rt\Live\Chat\Actions\SendChatMessage;
use He4rt\Live\Chat\Exceptions\ChatMessageRejected;
use He4rt\Live\Events\ChatMessageSent;
use He4rt\Live\Models\Live;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function (): void {
    Event::fake([ChatMessageSent::class]);
});

it('persiste a mensagem no pipeline de atividade com identidade He4rtLives', function (): void {
    $user = User::factory()->create();
    $live = Live::factory()->onAir()->create();

    $data = resolve(SendChatMessage::class)->execute($user, $live, 'salve chat!');

    $message = Message::query()->sole();
    $identity = ExternalIdentity::query()->where('provider', IdentityProvider::He4rtLives)->sole();

    expect($message->external_identity_id)->toBe($identity->id)
        ->and($identity->model_id)->toBe($user->id)
        ->and($identity->external_account_id)->toBe($user->id)
        ->and($message->channel_id)->toBe($live->id)
        ->and($message->content)->toBe('salve chat!')
        ->and($message->obtained_experience)->toBe(0)
        ->and($message->kind)->toBe(MessageKind::Default)
        ->and($message->source_kind)->toBe(MessageSourceKind::User)
        ->and($data->content)->toBe('salve chat!');
    Event::assertDispatched(fn (ChatMessageSent $e): bool => $e->liveId === $live->id);
});

it('reusa a identidade He4rtLives em envios seguintes', function (): void {
    $user = User::factory()->create();
    $live = Live::factory()->onAir()->create();
    $action = resolve(SendChatMessage::class);

    $action->execute($user, $live, 'primeira');
    $action->execute($user, $live, 'segunda');

    expect(ExternalIdentity::query()->where('provider', IdentityProvider::He4rtLives)->count())->toBe(1);
});

it('rejeita usuário banido', function (): void {
    $user = User::factory()->create(['banned_at' => now()]);
    $live = Live::factory()->onAir()->create();

    resolve(SendChatMessage::class)->execute($user, $live, 'oi');
})->throws(ChatMessageRejected::class);

it('rejeita usuário suspenso', function (): void {
    $user = User::factory()->create(['suspended_until' => now()->addDay()]);
    $live = Live::factory()->onAir()->create();

    resolve(SendChatMessage::class)->execute($user, $live, 'oi');
})->throws(ChatMessageRejected::class);

it('rejeita envio em live encerrada', function (): void {
    $user = User::factory()->create();
    $live = Live::factory()->ended()->create();

    resolve(SendChatMessage::class)->execute($user, $live, 'oi');
})->throws(ChatMessageRejected::class);

it('aplica rate limit de 5 mensagens por 10 segundos', function (): void {
    $user = User::factory()->create();
    $live = Live::factory()->onAir()->create();
    RateLimiter::clear('live-chat:'.$user->id);
    $action = resolve(SendChatMessage::class);

    foreach (range(1, 5) as $i) {
        $action->execute($user, $live, "mensagem {$i}");
    }

    $action->execute($user, $live, 'mensagem 6');
})->throws(ChatMessageRejected::class);
