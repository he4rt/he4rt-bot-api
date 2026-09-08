<?php

declare(strict_types=1);

namespace He4rt\Portal\Retrospective;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * A seção que apresenta a He4rt, entre a capa e os blocos das fontes.
 *
 * Fica FORA do snapshot de propósito: quem a He4rt é não muda a cada recorte, e
 * o snapshot existe para congelar números que mudam. A copy é editorial (mora
 * nas partials) e os poucos números vêm do período da edição — nada aqui soma
 * fontes, que continuaria proibido (ADR-0001).
 *
 * Dona única da lista e da ordem: o deck do portal desenha por aqui e o Deck
 * Builder conta por aqui para saber em que índice cada slide caiu. Um segundo
 * lugar com a mesma lista mandaria o preview para o slide vizinho.
 */
final class AboutSection
{
    /**
     * @return list<AboutSlide>
     */
    public static function slides(): array
    {
        return [
            new AboutSlide('manifest', 'A He4rt'),
            new AboutSlide('events', 'Eventos'),
            new AboutSlide('rituals', 'Iniciativas'),
            new AboutSlide('join', 'Onde entrar'),
        ];
    }

    public static function count(): int
    {
        return count(self::slides());
    }

    public static function find(string $key): ?AboutSlide
    {
        foreach (self::slides() as $slide) {
            if ($slide->key === $key) {
                return $slide;
            }
        }

        return null;
    }

    /**
     * Posição do slide DENTRO da seção (0-based). Null quando a chave não existe
     * — uma seleção antiga apontando para um slide removido não pode virar índice.
     */
    public static function positionOf(string $key): ?int
    {
        foreach (self::slides() as $position => $slide) {
            if ($slide->key === $key) {
                return $position;
            }
        }

        return null;
    }

    public static function foundedAt(): CarbonImmutable
    {
        return CarbonImmutable::parse(config()->string('he4rt.founded_at'));
    }

    /**
     * Quantos anos a comunidade tinha no fim do recorte. Ancorado no FIM e não em
     * hoje: um deck de 2024 reaberto em 2030 continua contando a idade que a He4rt
     * tinha quando aquilo aconteceu.
     */
    public static function ageAt(CarbonInterface $until): int
    {
        return (int) self::foundedAt()->diffInYears($until, absolute: true);
    }
}
