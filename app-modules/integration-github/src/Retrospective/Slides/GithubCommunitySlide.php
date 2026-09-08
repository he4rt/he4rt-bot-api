<?php

declare(strict_types=1);

namespace He4rt\IntegrationGithub\Retrospective\Slides;

use He4rt\Community\Retrospective\Contracts\Slide;

/**
 * A cauda de contribuidores além do núcleo. Projeta a prop people do componente
 * Blade retro.slides.github.community (que fatia a partir do 6º).
 */
final readonly class GithubCommunitySlide implements Slide
{
    /**
     * @param  list<array<string, mixed>>  $people
     */
    public function __construct(
        private array $people,
    ) {}

    public function kind(): string
    {
        return 'github.community';
    }

    /**
     * @return array{people: list<array<string, mixed>>}
     */
    public function toArray(): array
    {
        return ['people' => $this->people];
    }
}
