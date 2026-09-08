<?php

declare(strict_types=1);

namespace He4rt\IntegrationGithub\Retrospective\Slides;

use He4rt\Community\Retrospective\Contracts\Slide;

/**
 * Um repositório do recorte (um card por repo). O index é a posição no bloco do
 * GitHub, atribuída pela fonte na montagem.
 */
final readonly class GithubRepoSlide implements Slide
{
    /**
     * @param  array<string, mixed>  $repo
     */
    public function __construct(
        private array $repo,
        private int $index,
    ) {}

    public function kind(): string
    {
        return 'github.repos';
    }

    /**
     * @return array{repo: array<string, mixed>, index: int}
     */
    public function toArray(): array
    {
        return ['repo' => $this->repo, 'index' => $this->index];
    }
}
