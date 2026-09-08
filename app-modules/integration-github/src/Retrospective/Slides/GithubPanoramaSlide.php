<?php

declare(strict_types=1);

namespace He4rt\IntegrationGithub\Retrospective\Slides;

use He4rt\Community\Retrospective\Contracts\Slide;

/**
 * Painel de números agregados do GitHub. Projeta exatamente as props que o
 * componente Blade retro.slides.github.panorama já consumia.
 */
final readonly class GithubPanoramaSlide implements Slide
{
    /**
     * @param  array<string, int>  $meta
     */
    public function __construct(
        private array $meta,
    ) {}

    public function kind(): string
    {
        return 'github.panorama';
    }

    /**
     * @return array{meta: array<string, int>}
     */
    public function toArray(): array
    {
        return ['meta' => $this->meta];
    }
}
