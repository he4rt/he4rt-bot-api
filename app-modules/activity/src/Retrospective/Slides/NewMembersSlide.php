<?php

declare(strict_types=1);

namespace He4rt\Activity\Retrospective\Slides;

use He4rt\Community\Retrospective\Contracts\Slide;

/**
 * Novas pessoas e boosts do recorte (membership_events, escopo por occurred_at).
 */
final readonly class NewMembersSlide implements Slide
{
    public function __construct(
        private int $joins,
        private int $boosts,
    ) {}

    public function kind(): string
    {
        return 'discord.new_members';
    }

    /**
     * @return array{joins: int, boosts: int}
     */
    public function toArray(): array
    {
        return ['joins' => $this->joins, 'boosts' => $this->boosts];
    }
}
