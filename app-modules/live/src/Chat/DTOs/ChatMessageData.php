<?php

declare(strict_types=1);

namespace He4rt\Live\Chat\DTOs;

use Carbon\CarbonInterface;
use He4rt\Activity\Message\Models\Message;
use He4rt\Identity\User\Models\User;

/** Projeção pública de uma mensagem do chat, usada no broadcast e na renderização. */
final readonly class ChatMessageData
{
    public function __construct(
        public string $id,
        public string $authorName,
        public string $authorUsername,
        public string $authorAvatarUrl,
        public string $content,
        public CarbonInterface $sentAt,
    ) {}

    public static function fromMessage(Message $message, User $author): self
    {
        return new self(
            id: $message->id,
            authorName: $author->name,
            authorUsername: $author->username,
            authorAvatarUrl: $author->getFilamentAvatarUrl(),
            content: $message->content,
            sentAt: $message->sent_at ?? $message->created_at ?? now(),
        );
    }

    /** @return array<string, string> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'authorName' => $this->authorName,
            'authorUsername' => $this->authorUsername,
            'authorAvatarUrl' => $this->authorAvatarUrl,
            'content' => $this->content,
            'sentAt' => $this->sentAt->toIso8601String(),
        ];
    }
}
