<?php

declare(strict_types=1);

namespace He4rt\Identity\User\Enums;

use App\Enums\Concerns\StringifyEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

/**
 * Estado de acesso de um usuário, derivado de `banned_at` e `suspended_until`.
 *
 * Não existe coluna correspondente: quem escreve essas duas datas é
 * {@see \He4rt\Moderation\Platform\WebModerationAdapter}, a partir de um caso
 * de moderação. Aqui elas só são lidas.
 */
enum UserSituation: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    use StringifyEnum;

    case Active = 'active';
    case Suspended = 'suspended';
    case Banned = 'banned';

    public function getLabel(): string
    {
        return match ($this) {
            self::Active => 'Ativo',
            self::Suspended => 'Suspenso',
            self::Banned => 'Banido',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Suspended => 'warning',
            self::Banned => 'danger',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Active => 'Sem restrição ativa',
            self::Suspended => 'Acesso bloqueado até a data de término',
            self::Banned => 'Acesso revogado por decisão de moderação',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Active => Heroicon::OutlinedCheckCircle,
            self::Suspended => Heroicon::OutlinedClock,
            self::Banned => Heroicon::OutlinedNoSymbol,
        };
    }
}
