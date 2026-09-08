<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Retrospectives\Support;

/**
 * Um bloco de fonte na coluna de estrutura do Deck Builder, já na posição
 * editorial. `curatable` é o resultado do instanceof CuratableSource: quando é
 * false o bloco continua na timeline com ordem e on/off, só sem chips de slide
 * nem picker de exclusions (ADR-0002).
 */
final readonly class SourceBlock
{
    /**
     * @param  list<SlideEntry>  $slides
     */
    public function __construct(
        public string $key,
        public string $label,
        public bool $visible,
        public bool $curatable,
        public array $slides = [],
    ) {}
}
