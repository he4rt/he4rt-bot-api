<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Retrospectives\Support;

/**
 * Uma miniatura do filmstrip: o slide como ele está no snapshot, pronto para
 * render, mais o rótulo do catálogo e o on/off do kind.
 *
 * `kind` é a identidade editorial e `props` é a instância. Um kind pode emitir
 * várias instâncias (github.repos = um card por repositório): elas viram várias
 * miniaturas, mas o toggle continua sendo um só, do kind (ADR-0002).
 */
final readonly class FilmstripSlide
{
    /**
     * @param  string|null  $view  a partial que desenha a miniatura; null quando o
     *                             kind está no catálogo mas não rendeu nada no recorte
     * @param  array<string, mixed>  $props
     * @param  int|null  $index  posição no deck renderizado; null quando o slide
     *                           não está na composição e portanto não é navegável
     */
    public function __construct(
        public string $kind,
        public string $label,
        public bool $visible,
        public ?string $view,
        public array $props,
        public ?int $index = null,
    ) {}
}
