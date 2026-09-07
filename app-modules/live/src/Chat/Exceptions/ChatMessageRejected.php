<?php

declare(strict_types=1);

namespace He4rt\Live\Chat\Exceptions;

use DomainException;

final class ChatMessageRejected extends DomainException
{
    public static function userBlocked(): self
    {
        return new self('Sua conta não pode enviar mensagens no chat.');
    }

    public static function liveNotAcceptingMessages(): self
    {
        return new self('Esta live não está mais recebendo mensagens.');
    }

    public static function rateLimited(int $seconds): self
    {
        return new self("Calma! Aguarde {$seconds}s para enviar de novo.");
    }
}
