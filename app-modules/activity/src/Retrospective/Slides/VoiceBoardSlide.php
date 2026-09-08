<?php

declare(strict_types=1);

namespace He4rt\Activity\Retrospective\Slides;

use He4rt\Community\Retrospective\Contracts\Slide;

/**
 * Painel de voz: os totais do recorte, as arenas (canais por entrada), quem mais
 * viveu no voice e o histograma de entradas por hora.
 *
 * channel_name existe em voice_messages, então o ranking de canal não precisa
 * resolver nome no portal; só as pessoas do topo custam uma resolução de nome.
 */
final readonly class VoiceBoardSlide implements Slide
{
    /**
     * @param  int  $participants  pessoas distintas que passaram por alguma call
     * @param  int  $joins  eventos de entrada (state = joined)
     * @param  int  $earners  pessoas que tiraram XP da presença
     * @param  array{date: string, joins: int}|null  $peak  o dia mais movimentado
     * @param  list<array{name: string, joins: int, people: int, xp: int, rooms: int}>  $channels
     * @param  list<array{name: string, xp: int, joins: int, channels: int}>  $people
     * @param  list<array{hour: int, joins: int}>  $hours  sempre as 24 posições
     */
    public function __construct(
        private int $participants,
        private int $joins,
        private int $xp,
        private int $earners,
        private ?array $peak,
        private array $channels,
        private array $people,
        private array $hours,
    ) {}

    public function kind(): string
    {
        return 'discord.voice_board';
    }

    /**
     * @return array{
     *     participants: int,
     *     joins: int,
     *     xp: int,
     *     earners: int,
     *     peak: array{date: string, joins: int}|null,
     *     channels: list<array{name: string, joins: int, people: int, xp: int, rooms: int}>,
     *     people: list<array{name: string, xp: int, joins: int, channels: int}>,
     *     hours: list<array{hour: int, joins: int}>,
     * }
     */
    public function toArray(): array
    {
        return [
            'participants' => $this->participants,
            'joins' => $this->joins,
            'xp' => $this->xp,
            'earners' => $this->earners,
            'peak' => $this->peak,
            'channels' => $this->channels,
            'people' => $this->people,
            'hours' => $this->hours,
        ];
    }
}
