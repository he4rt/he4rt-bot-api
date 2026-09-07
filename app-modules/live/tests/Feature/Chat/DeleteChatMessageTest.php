<?php

declare(strict_types=1);

use He4rt\Activity\Message\Models\Message;
use He4rt\Activity\Moderation\Enums\ModerationType;
use He4rt\Activity\Moderation\Models\ModerationEvent;
use He4rt\Identity\User\Models\User;
use He4rt\Live\Chat\Actions\DeleteChatMessage;
use He4rt\Live\Chat\Actions\SendChatMessage;
use He4rt\Live\Events\ChatMessageDeleted;
use He4rt\Live\Models\Live;
use Illuminate\Support\Facades\Event;

it('apaga a mensagem, registra moderação e broadcasta a remoção', function (): void {
    Event::fake([ChatMessageDeleted::class]);
    $author = User::factory()->create();
    $moderator = User::factory()->create();
    $live = Live::factory()->onAir()->create();
    $data = resolve(SendChatMessage::class)->execute($author, $live, 'mensagem imprópria');
    $message = Message::query()->sole();

    resolve(DeleteChatMessage::class)->execute($message, $moderator);

    $event = ModerationEvent::query()->sole();

    expect(Message::query()->count())->toBe(0)
        ->and($event->type)->toBe(ModerationType::MessageDeleted)
        ->and($event->metadata)->toMatchArray(['content' => 'mensagem imprópria', 'live_id' => $live->id]);
    Event::assertDispatched(fn (ChatMessageDeleted $e): bool => $e->messageId === $data->id && $e->liveId === $live->id);
});
