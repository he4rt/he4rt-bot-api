<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Contributions\Timeline\DTOs;

final readonly class VoiceDay
{
    public function __construct(
        public int $sessions,
        public int $people,
        public int $xp,
    ) {}

    /**
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return [
            'sessions' => $this->sessions,
            'people' => $this->people,
            'xp' => $this->xp,
        ];
    }
}
