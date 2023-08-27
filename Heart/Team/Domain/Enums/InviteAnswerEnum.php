<?php

namespace Heart\Team\Domain\Enums;

use Symfony\Component\HttpFoundation\Response;

enum InviteAnswerEnum: string
{
    case ACCEPT = 'accept';
    case DECLINE = 'decline';

    public function getMessage(): string
    {
        return match ($this) {
            self::ACCEPT => 'Invite accepted',
            self::DECLINE => 'Invite Declined'
        };
    }

    public function getCode(): int
    {
        return match ($this) {
            self::ACCEPT => Response::HTTP_CREATED,
            self::DECLINE => Response::HTTP_OK,
        };
    }
}
