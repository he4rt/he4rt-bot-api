<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Contributions\Timeline\DTOs;

final readonly class MessageDay
{
    public function __construct(
        public int $messages,
        public int $people,
        public int $xp,
    ) {}

    /**
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return [
            'messages' => $this->messages,
            'people' => $this->people,
            'xp' => $this->xp,
        ];
    }
}
