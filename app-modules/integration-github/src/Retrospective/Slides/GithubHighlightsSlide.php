<?php

declare(strict_types=1);

namespace He4rt\IntegrationGithub\Retrospective\Slides;

use He4rt\Community\Retrospective\Contracts\Slide;

/**
 * Os PRs de maior volume do recorte. Projeta a prop highlights do componente
 * Blade retro.slides.github.highlights.
 */
final readonly class GithubHighlightsSlide implements Slide
{
    /**
     * @param  list<array<string, mixed>>  $highlights
     */
    public function __construct(
        private array $highlights,
    ) {}

    public function kind(): string
    {
        return 'github.highlights';
    }

    /**
     * @return array{highlights: list<array<string, mixed>>}
     */
    public function toArray(): array
    {
        return ['highlights' => $this->highlights];
    }
}
