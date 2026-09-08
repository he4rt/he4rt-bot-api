<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Retrospectives\Support;

/**
 * Um bloco de fonte na tira do Deck Builder: o cabeçalho que liga/desliga a fonte
 * e as miniaturas dos slides que ela emitiu.
 *
 * Vem do CATÁLOGO, não da composição: o grupo aparece mesmo desligado e mesmo sem
 * slide nenhum, porque é nele que mora o botão que o religa.
 */
final readonly class FilmstripGroup
{
    /**
     * @param  list<FilmstripSlide>  $slides
     */
    public function __construct(
        public string $key,
        public string $label,
        public bool $visible,
        public bool $curatable,
        public array $slides = [],
    ) {}

    public function slideCount(): int
    {
        return count($this->slides);
    }
}
