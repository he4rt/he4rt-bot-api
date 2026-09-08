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
 * Ciclo de vida editorial de uma retrospectiva. Não é uma escala de risco (não
 * há ramp claro->danger): é um fluxo com cores semânticas próprias. Publishing é
 * o estado transitório enquanto o job congela o snapshot.
 */
enum RetrospectiveStatus: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    use StringifyEnum;

    case Draft = 'draft';
    case Publishing = 'publishing';
    case Published = 'published';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Publishing => 'Publicando',
            self::Published => 'Publicado',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Publishing => 'warning',
            self::Published => 'success',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Draft => 'Em edição: coleta o dado ao vivo enquanto o operador cura',
            self::Publishing => 'Congelando o snapshot em segundo plano',
            self::Published => 'Publicada: a página pública lê o snapshot congelado',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Draft => Heroicon::OutlinedPencilSquare,
            self::Publishing => Heroicon::OutlinedArrowPath,
            self::Published => Heroicon::OutlinedCheckCircle,
        };
    }

    public function isDraft(): bool
    {
        return $this === self::Draft;
    }

    public function isPublished(): bool
    {
        return $this === self::Published;
    }
}
