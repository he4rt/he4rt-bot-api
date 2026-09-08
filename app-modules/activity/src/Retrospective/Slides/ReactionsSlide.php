<?php

declare(strict_types=1);

namespace He4rt\Activity\Retrospective\Slides;

use He4rt\Community\Retrospective\Contracts\Slide;

/**
 * Reações do recorte: total e o topo de emojis. Reações não têm tempo próprio,
 * então o escopo vem das mensagens reagidas dentro do período.
 */
final readonly class ReactionsSlide implements Slide
{
    /**
     * @param  list<array{name: string, count: int, custom: bool}>  $emojis
     */
    public function __construct(
        private int $total,
        private array $emojis,
    ) {}

    public function kind(): string
    {
        return 'discord.reactions';
    }

    /**
     * @return array{total: int, emojis: list<array{name: string, count: int, custom: bool}>}
     */
    public function toArray(): array
    {
        return ['total' => $this->total, 'emojis' => $this->emojis];
    }
}
