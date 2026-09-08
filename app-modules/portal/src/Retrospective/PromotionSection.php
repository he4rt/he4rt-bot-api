<?php

declare(strict_types=1);

namespace He4rt\Portal\Retrospective;

use He4rt\Community\Retrospective\DTOs\DeckConfig;
use He4rt\Community\Retrospective\DTOs\PromotionCard;
use He4rt\Community\Retrospective\Enums\PromotionStage;

/**
 * O ritual da tag He4rt, entre os blocos das fontes e o fecho.
 *
 * Posição fixa e de propósito: é o fecho humano do deck — os números todos vêm
 * antes justamente para justificar quem aparece aqui. Deixá-la arrastável
 * permitiria premiar alguém antes de mostrar a prova, então ordem não é
 * parâmetro. On/off por slide continua sendo (uma edição sem entrega de tag
 * desliga a revelação e mantém os destaques).
 *
 * Dona única da lista e da ordem, como a AboutSection: o deck do portal desenha
 * por aqui e o Deck Builder conta por aqui para saber em que índice cada slide
 * caiu. Uma segunda lista mandaria o preview para o slide vizinho.
 */
final class PromotionSection
{
    public const string SPOTLIGHT = 'he4rt.spotlight';

    public const string TAG = 'he4rt.tag';

    /**
     * O catálogo: o que a seção PODE desenhar, resolvido sem tocar dado nenhum.
     *
     * @return list<PromotionSlide>
     */
    public static function catalog(): array
    {
        return [
            new PromotionSlide(
                kind: self::SPOTLIGHT,
                label: 'Destaques',
                hint: 'Quem segurou a comunidade no recorte, com os números na frente.',
                stage: PromotionStage::Spotlight,
            ),
            new PromotionSlide(
                kind: self::TAG,
                label: 'A tag He4rt',
                hint: 'Quem recebeu a tag, revelado passo a passo com as setas.',
                stage: PromotionStage::Promoted,
            ),
        ];
    }

    /**
     * Os slides que o deck realmente desenha: os do catálogo que a curadoria não
     * desligou E que têm gente.
     *
     * Slide vazio some em vez de virar uma tela com título e nada embaixo — um
     * ritual sem ninguém não é um momento do deck, é um buraco nele.
     *
     * @param  list<PromotionCard>  $cards
     * @return list<PromotionSlide>
     */
    public static function slides(array $cards, DeckConfig $config): array
    {
        $slides = [];

        foreach (self::catalog() as $slide) {
            if (!$config->showsSlide($slide->kind)) {
                continue;
            }

            $filled = $slide->withCards(self::cardsFor($cards, $slide->stage));

            if (!$filled->isEmpty()) {
                $slides[] = $filled;
            }
        }

        return $slides;
    }

    public static function find(string $kind): ?PromotionSlide
    {
        foreach (self::catalog() as $slide) {
            if ($slide->kind === $kind) {
                return $slide;
            }
        }

        return null;
    }

    /**
     * @param  list<PromotionCard>  $cards
     * @return list<PromotionCard>
     */
    public static function cardsFor(array $cards, PromotionStage $stage): array
    {
        return array_values(array_filter(
            $cards,
            static fn (PromotionCard $card): bool => $card->stage === $stage,
        ));
    }
}
