<?php

declare(strict_types=1);

namespace He4rt\Community\Retrospective\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

/**
 * Até onde uma pessoa chegou no ritual da tag He4rt naquele recorte. É escala
 * ordenada — destaque é o degrau antes de receber a tag —, então a cor sobe de
 * `info` até `danger`, e cada estágio manda no seu próprio slide: destaque
 * desenha a grade, promovido desenha a revelação.
 */
enum PromotionStage: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    case Spotlight = 'spotlight';
    case Promoted = 'promoted';

    public function getLabel(): string
    {
        return match ($this) {
            self::Spotlight => 'Destaque',
            self::Promoted => 'Recebeu a tag',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Spotlight => 'info',
            self::Promoted => 'danger',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Spotlight => 'Gente que segurou a comunidade no recorte, com os números na frente.',
            self::Promoted => 'Quem recebeu a tag He4rt nesta edição. Revelado passo a passo no deck.',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Spotlight => Heroicon::OutlinedStar,
            self::Promoted => Heroicon::OutlinedHeart,
        };
    }
}
