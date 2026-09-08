<?php

declare(strict_types=1);

namespace He4rt\Community\Retrospective\Slides;

use He4rt\Community\Retrospective\Contracts\Slide;

/**
 * Slide reidratado de um snapshot congelado. As classes concretas de slide moram
 * nos módulos que emitem o dado (GithubPanoramaSlide em integration-github,
 * VoiceBoardSlide em activity) e o domínio community não pode importá-las
 * (Domain -> Integration é proibido). Ao congelar, só o contrato importa: kind +
 * props. O FrozenSlide carrega esse par e satisfaz Slide, então o portal renderiza
 * o snapshot pela mesma convenção kind -> Blade, sem conhecer o tipo original.
 */
final readonly class FrozenSlide implements Slide
{
    /**
     * @param  array<string, mixed>  $props
     */
    public function __construct(
        private string $kind,
        private array $props,
    ) {}

    public function kind(): string
    {
        return $this->kind;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->props;
    }
}
