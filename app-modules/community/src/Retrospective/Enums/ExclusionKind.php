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
 * O que um candidato a exclusion é. Não é escala ordenada (esconder uma pessoa
 * não é "mais grave" que esconder um item): são dois tipos distintos, com cores
 * distintas, e o builder agrupa o picker por eles.
 */
enum ExclusionKind: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    use StringifyEnum;

    case Item = 'item';
    case Person = 'person';

    public function getLabel(): string
    {
        return match ($this) {
            self::Item => 'Item',
            self::Person => 'Pessoa',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Item => 'info',
            self::Person => 'warning',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Item => 'Some do deck inteiro daquela fonte (PR, issue, mensagem)',
            self::Person => 'Some toda a contribuição da pessoa no recorte, inclusive dos números',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Item => Heroicon::OutlinedDocumentText,
            self::Person => Heroicon::OutlinedUser,
        };
    }
}
