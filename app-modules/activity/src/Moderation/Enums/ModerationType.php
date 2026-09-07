<?php

declare(strict_types=1);

namespace He4rt\Activity\Moderation\Enums;

use App\Enums\Concerns\StringifyEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ModerationType: string implements HasColor, HasLabel
{
    use StringifyEnum;

    case Ban = 'ban';
    case Unban = 'unban';
    case Mute = 'mute';
    case Unmute = 'unmute';
    case Warn = 'warn';
    case Kick = 'kick';
    case Suspension = 'suspension';
    case MessageDeleted = 'message_deleted';

    public function getLabel(): string
    {
        return match ($this) {
            self::Ban => 'Ban',
            self::Unban => 'Unban',
            self::Mute => 'Mute',
            self::Unmute => 'Unmute',
            self::Warn => 'Warning',
            self::Kick => 'Kick',
            self::Suspension => 'Suspension',
            self::MessageDeleted => 'Mensagem apagada',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Ban, self::Kick => 'danger',
            self::Unban, self::Unmute => 'success',
            self::Mute, self::Suspension => 'warning',
            self::Warn, self::MessageDeleted => 'info',
        };
    }
}
