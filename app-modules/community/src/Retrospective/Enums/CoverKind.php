<?php

declare(strict_types=1);

namespace He4rt\Community\Retrospective\Enums;

use App\Enums\Concerns\StringifyEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

/**
 * Para quem a edição abre. O mesmo deck serve a dois rituais: o balanço do
 * período (retrospectiva) e a recepção mensal de quem acabou de chegar
 * (onboarding). Só a capa muda; o resto do deck é a mesma prova.
 *
 * Não é escala: dois públicos distintos, cada um com cor própria.
 */
enum CoverKind: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    use StringifyEnum;

    case Retrospective = 'retrospective';
    case Onboarding = 'onboarding';

    public function getLabel(): string
    {
        return match ($this) {
            self::Retrospective => 'Retrospectiva',
            self::Onboarding => 'Onboarding',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Retrospective => 'primary',
            self::Onboarding => 'success',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Retrospective => 'Abre como balanço do período: "quem fez a He4rt bater".',
            self::Onboarding => 'Abre como boas-vindas ao evento mensal de novos membros, com edição e apresentador.',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Retrospective => Heroicon::OutlinedSparkles,
            self::Onboarding => Heroicon::OutlinedHandRaised,
        };
    }

    public function isOnboarding(): bool
    {
        return $this === self::Onboarding;
    }
}
