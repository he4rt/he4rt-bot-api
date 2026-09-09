<?php

declare(strict_types=1);

namespace He4rt\Activity\Reaction\Enums;

use App\Enums\Concerns\StringifyEnum;
use Filament\Support\Contracts\HasLabel;

/**
 * Conjunto fechado de reações da timeline web. O banco guarda só o value;
 * rótulo e glifo vivem aqui e nunca são persistidos.
 */
enum TimelineReaction: string implements HasLabel
{
    use StringifyEnum;

    case Like = 'like';
    case Love = 'love';
    case Laugh = 'laugh';
    case Celebrate = 'celebrate';
    case Fire = 'fire';
    case Sad = 'sad';

    public function getLabel(): string
    {
        return match ($this) {
            self::Like => 'Curtir',
            self::Love => 'Amei',
            self::Laugh => 'Haha',
            self::Celebrate => 'Parabéns',
            self::Fire => 'Fogo',
            self::Sad => 'Triste',
        };
    }

    public function emoji(): string
    {
        return match ($this) {
            self::Like => '👍',
            self::Love => '❤️',
            self::Laugh => '😂',
            self::Celebrate => '🎉',
            self::Fire => '🔥',
            self::Sad => '😢',
        };
    }
}
