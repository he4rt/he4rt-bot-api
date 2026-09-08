<?php

declare(strict_types=1);

namespace He4rt\Portal\Articles\DTOs;

final readonly class ArticleTopic
{
    public function __construct(
        public string $tag,
        public int $count,
    ) {}

    /**
     * Fatia do acervo sob este tema. Na barra de proporção, é o que revela que
     * uma tag presente em quase todo artigo rotula em vez de filtrar.
     */
    public function share(int $total): float
    {
        return $total > 0 ? $this->count / $total : 0.0;
    }
}
