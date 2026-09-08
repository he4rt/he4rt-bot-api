<?php

declare(strict_types=1);

namespace He4rt\Community\Retrospective\Contracts;

/**
 * Unidade renderável de uma fonte: um painel do deck, identificado por um kind
 * (ex.: "github.repos", "discord.voice_board") que o portal mapeia para um
 * componente Blade por convenção. Slides carregam DADO, nunca markup.
 *
 * toArray() é a projeção de apresentação (props do componente Blade) e também a
 * fronteira de serialização usada pelo snapshot persistido da Fase 2.
 */
interface Slide
{
    public function kind(): string;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
