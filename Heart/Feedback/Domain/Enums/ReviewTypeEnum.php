<?php

namespace Heart\Feedback\Domain\Enums;

enum ReviewTypeEnum: string
{
    case APPROVED = 'approved';
    case DECLINED = 'declined';

    public static function getTypes(): array
    {
        return [
            self::APPROVED->value,
            self::DECLINED->value,
        ];
    }
}
