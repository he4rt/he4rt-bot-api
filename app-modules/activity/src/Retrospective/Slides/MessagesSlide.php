<?php

declare(strict_types=1);

namespace He4rt\Activity\Retrospective\Slides;

use He4rt\Community\Retrospective\Contracts\Slide;

/**
 * Painel de conversas, o irmão de texto do VoiceBoardSlide: os totais do
 * recorte, o ritmo da semana, quem mais conversou (nome resolvido em PHP só
 * para as N pessoas do ranking) e o histograma de mensagens por hora.
 */
final readonly class MessagesSlide implements Slide
{
    /**
     * @param  int  $people  pessoas distintas que mandaram mensagem
     * @param  array{date: string, messages: int}|null  $peak  o dia mais falante
     * @param  list<array{name: string, messages: int, xp: int, reactions: int}>  $chatters
     * @param  list<array{weekday: int, messages: int}>  $days  sempre as 7 posições (ISO: 1 = segunda)
     * @param  list<array{hour: int, messages: int}>  $hours  sempre as 24 posições
     */
    public function __construct(
        private int $total,
        private int $withReactions,
        private int $pinned,
        private int $people,
        private ?array $peak,
        private array $chatters,
        private array $days,
        private array $hours,
    ) {}

    public function kind(): string
    {
        return 'discord.messages';
    }

    /**
     * @return array{
     *     total: int,
     *     with_reactions: int,
     *     pinned: int,
     *     people: int,
     *     peak: array{date: string, messages: int}|null,
     *     chatters: list<array{name: string, messages: int, xp: int, reactions: int}>,
     *     days: list<array{weekday: int, messages: int}>,
     *     hours: list<array{hour: int, messages: int}>,
     * }
     */
    public function toArray(): array
    {
        return [
            'total' => $this->total,
            'with_reactions' => $this->withReactions,
            'pinned' => $this->pinned,
            'people' => $this->people,
            'peak' => $this->peak,
            'chatters' => $this->chatters,
            'days' => $this->days,
            'hours' => $this->hours,
        ];
    }
}
