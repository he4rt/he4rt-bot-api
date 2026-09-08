<?php

declare(strict_types=1);

namespace He4rt\Activity\Tracking\Contracts;

/**
 * O que a origem sabe dizer sobre a contribuição que a Interaction aponta.
 *
 * A Interaction guarda quem, o quê e quando; o título, o contexto e o link vivem
 * na fonte e são lidos de lá. Copiá-los para o metadata criava duas versões da
 * mesma verdade — e a que ficava na Interaction envelhecia sozinha.
 */
interface ContributionDetail
{
    public function contributionTitle(): string;

    /**
     * Onde a contribuição aconteceu, na linguagem da origem:
     * "he4rt/heartdevs.com #482", "dev.to".
     */
    public function contributionContext(): string;

    public function contributionUrl(): ?string;
}
