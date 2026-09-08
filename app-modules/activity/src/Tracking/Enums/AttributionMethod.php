<?php

declare(strict_types=1);

namespace He4rt\Activity\Tracking\Enums;

use App\Enums\Concerns\StringifyEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

/**
 * Quão firme é o laço entre a contribuição e a identidade que a recebeu.
 *
 * Escala ordenada por risco de atribuição errada: a fonte que já sabia o dono no
 * topo, o palpite sobre um nome mutável embaixo. É vocabulário do domínio — o
 * "actor_id" do GitHub é um ExternalId como qualquer outro id de conta.
 */
enum AttributionMethod: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    use StringifyEnum;

    case Owned = 'owned';

    case ExternalId = 'external_id';

    case Handle = 'handle';

    public function getLabel(): string
    {
        return match ($this) {
            self::Owned => 'Origem',
            self::ExternalId => 'ID externo',
            self::Handle => 'Nome de usuário',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Owned => 'gray',
            self::ExternalId => 'info',
            self::Handle => 'danger',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Owned => 'A própria fonte entregou o dono — não houve casamento a errar',
            self::ExternalId => 'Casada pelo identificador imutável da conta na origem',
            self::Handle => 'Casada pelo nome de usuário, que pode mudar de dono na origem',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Owned => Heroicon::OutlinedLink,
            self::ExternalId => Heroicon::OutlinedFingerPrint,
            self::Handle => Heroicon::OutlinedAtSymbol,
        };
    }
}
