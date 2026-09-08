<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Contributions\Timeline\DTOs;

use Carbon\CarbonImmutable;

final readonly class TimelineDay
{
    public function __construct(
        public CarbonImmutable $date,
        public GithubDay $github,
        public MessageDay $messages,
        public VoiceDay $voice,
    ) {}

    /**
     * @return array{date: string, gh: array<string, int>, ms: array<string, int>, vc: array<string, int>}
     */
    public function toArray(): array
    {
        return [
            'date' => $this->date->toDateString(),
            'gh' => $this->github->toArray(),
            'ms' => $this->messages->toArray(),
            'vc' => $this->voice->toArray(),
        ];
    }
}
