<?php

declare(strict_types=1);

namespace He4rt\Live\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

/** Estágio editorial de uma live: criada no admin, no ar com sinal, encerrada. */
enum LiveStatus: string implements HasColor, HasDescription, HasLabel
{
    case Created = 'created';
    case OnAir = 'on_air';
    case Ended = 'ended';

    public function getLabel(): string
    {
        return match ($this) {
            self::Created => 'Criada',
            self::OnAir => 'No ar',
            self::Ended => 'Encerrada',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Created => 'gray',
            self::OnAir => 'success',
            self::Ended => 'danger',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Created => 'Aguardando o primeiro sinal do OBS',
            self::OnAir => 'Transmissão em andamento',
            self::Ended => 'Encerrada pelo admin — a stream key não vale mais',
        };
    }
}
