<?php

declare(strict_types=1);

namespace He4rt\Activity\Reaction\Enums;

use App\Enums\Concerns\StringifyEnum;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

/**
 * Conjunto fechado de reações da timeline web. O banco guarda só o value;
 * rótulo, cor e glifo vivem aqui e nunca são persistidos.
 */
enum TimelineReaction: string implements HasColor, HasDescription, HasLabel
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

    /**
     * Conjunto não-ordenado: não há ramp claro->danger. Cada cor puxa a do
     * próprio glifo, para o rótulo textual não brigar com o emoji ao lado.
     *
     * @return array<int, string>
     */
    public function getColor(): array
    {
        return match ($this) {
            self::Like => Color::Sky,
            self::Love => Color::Rose,
            self::Laugh => Color::Amber,
            self::Celebrate => Color::Violet,
            self::Fire => Color::Orange,
            self::Sad => Color::Slate,
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Like => 'Concorda ou apoia sem muito peso',
            self::Love => 'Guardou carinho pelo conteúdo',
            self::Laugh => 'Achou graça',
            self::Celebrate => 'Comemora uma conquista de alguém da comunidade',
            self::Fire => 'Reconhece que o conteúdo ficou excelente',
            self::Sad => 'Solidariza com uma notícia difícil',
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
