<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Contributions\Timeline\DTOs;

/**
 * Um tipo de contribuição do GitHub no streamgraph. A `key` casa com o campo do
 * dia (`gh.reviews`); a ordem visual e a cor saem da contagem, no cliente.
 */
final readonly class TimelineType
{
    public function __construct(
        public string $key,
        public string $label,
        public int $count,
    ) {}

    /**
     * @return array{key: string, label: string, count: int}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'count' => $this->count,
        ];
    }
}
