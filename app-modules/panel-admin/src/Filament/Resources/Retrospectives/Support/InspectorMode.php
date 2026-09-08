<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Retrospectives\Support;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

/**
 * Os modos do inspector do Deck Builder. Não é escala ordenada — são alvos
 * distintos de seleção, cada um escrevendo onde a Fase 2 já escrevia (ADR-0002)
 * —, então cada caso tem cor própria, sem rampa.
 *
 * `Promotion` é o único que escreve PESSOAS: a curadoria dele mexe no dado
 * exibido, como as exclusions, então salvar ali avisa que é preciso republicar.
 *
 * `About` é o caso que NÃO edita: a seção sobre a He4rt é copy fixa no portal.
 * Ele existe mesmo assim porque a tira precisa de um alvo para aqueles slides —
 * sem modo próprio, clicar na miniatura mandaria o preview para a capa.
 */
enum InspectorMode: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    case Cover = 'cover';
    case About = 'about';
    case Source = 'source';
    case Slide = 'slide';
    case Promotion = 'promotion';
    case Closing = 'closing';

    public function getLabel(): string
    {
        return match ($this) {
            self::Cover => 'Capa',
            self::About => 'A He4rt',
            self::Source => 'Bloco de fonte',
            self::Slide => 'Slide',
            self::Promotion => 'A tag He4rt',
            self::Closing => 'Fecho',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Cover => 'primary',
            self::About => 'warning',
            self::Source => 'info',
            self::Slide => 'success',
            self::Promotion => 'danger',
            self::Closing => 'gray',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Cover => 'Título, recorte e texto de abertura — colunas da edição.',
            self::About => 'Apresentação fixa da comunidade. Copy no portal, não na edição.',
            self::Source => 'Exibir a fonte e curar o que ela esconde do deck.',
            self::Slide => 'Exibir este tipo de slide. O toggle vale para o kind inteiro.',
            self::Promotion => 'Quem aparece no ritual da tag. Mexe nos números: exige republicar.',
            self::Closing => 'A mensagem que fecha o deck.',
        };
    }

    /**
     * Se o inspector deste modo tem o que salvar. Governa o botão Salvar: um botão
     * que não escreve nada é pior que nenhum — promete uma edição que não existe.
     */
    public function editable(): bool
    {
        return match ($this) {
            self::Cover, self::Source, self::Slide, self::Promotion, self::Closing => true,
            self::About => false,
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Cover => Heroicon::OutlinedSparkles,
            self::About => Heroicon::OutlinedHeart,
            self::Source => Heroicon::OutlinedSquares2x2,
            self::Slide => Heroicon::OutlinedRectangleGroup,
            self::Promotion => Heroicon::OutlinedHeart,
            self::Closing => Heroicon::OutlinedChatBubbleBottomCenterText,
        };
    }
}
