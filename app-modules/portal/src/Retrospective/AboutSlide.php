<?php

declare(strict_types=1);

namespace He4rt\Portal\Retrospective;

/**
 * Um slide da seção fixa sobre a He4rt. Carrega a chave (identidade, viaja na
 * seleção do builder) e o rótulo que a tira mostra — a view sai da convenção do
 * SlideView, não de um caminho guardado aqui.
 */
final readonly class AboutSlide
{
    public function __construct(
        public string $key,
        public string $label,
    ) {}

    public function view(): string
    {
        return SlideView::about($this->key);
    }
}
