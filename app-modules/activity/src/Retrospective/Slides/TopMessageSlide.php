<?php

declare(strict_types=1);

namespace He4rt\Activity\Retrospective\Slides;

use He4rt\Community\Retrospective\Contracts\Slide;

/**
 * As mensagens mais reagidas do recorte. Conteúdo é dado cru do Discord (pode
 * conter ruído); a curadoria/exclusion que filtra isso chega na Fase 2/3.
 */
final readonly class TopMessageSlide implements Slide
{
    /**
     * @param  list<array{content: string, author: string, reactions: int}>  $messages
     */
    public function __construct(
        private array $messages,
    ) {}

    public function kind(): string
    {
        return 'discord.top_message';
    }

    /**
     * @return array{messages: list<array{content: string, author: string, reactions: int}>}
     */
    public function toArray(): array
    {
        return ['messages' => $this->messages];
    }
}
