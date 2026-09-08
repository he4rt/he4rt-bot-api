<?php

declare(strict_types=1);

namespace He4rt\IntegrationGithub\Retrospective\Slides;

use He4rt\Community\Retrospective\Contracts\Slide;

/**
 * O núcleo de contribuidores (topo do ranking). Projeta a prop people do
 * componente Blade retro.slides.github.core.
 */
final readonly class GithubCoreSlide implements Slide
{
    /**
     * @param  list<array<string, mixed>>  $people
     */
    public function __construct(
        private array $people,
    ) {}

    public function kind(): string
    {
        return 'github.core';
    }

    /**
     * @return array{people: list<array<string, mixed>>}
     */
    public function toArray(): array
    {
        return ['people' => $this->people];
    }
}
