<?php

namespace Heart\Feedback\Domain\Enums;

enum ReviewTypeEnum: string
{
    case Approve = 'approved';
    case Decline = 'declined';

    public static function getTypes(): array
    {
        return [
            self::Approve->value,
            self::Decline->value,
        ];
    }
}
