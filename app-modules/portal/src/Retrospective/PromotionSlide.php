<?php

declare(strict_types=1);

namespace He4rt\Portal\Retrospective;

use He4rt\Community\Retrospective\DTOs\PromotionCard;
use He4rt\Community\Retrospective\Enums\PromotionStage;

/**
 * Um dos slides do ritual da tag He4rt: o kind (identidade editorial, é por ele
 * que a curadoria liga e desliga), o estágio que ele desenha e as pessoas
 * daquele estágio.
 *
 * A view sai da convenção do SlideView, como qualquer slide de fonte — o painel
 * mostra o mesmo caminho ao operador e não há um segundo mapa para divergir.
 */
final readonly class PromotionSlide
{
    /**
     * @param  list<PromotionCard>  $cards
     */
    public function __construct(
        public string $kind,
        public string $label,
        public string $hint,
        public PromotionStage $stage,
        public array $cards = [],
    ) {}

    /**
     * @param  list<PromotionCard>  $cards
     */
    public function withCards(array $cards): self
    {
        return new self($this->kind, $this->label, $this->hint, $this->stage, $cards);
    }

    public function view(): string
    {
        return SlideView::kind($this->kind);
    }

    public function isEmpty(): bool
    {
        return $this->cards === [];
    }

    /**
     * Quantos apertos de seta o slide consome antes de liberar a navegação.
     *
     * A revelação é granular — nome, números e motivo de cada pessoa, um passo
     * por vez — entre o passo 0, que abre com a pergunta e ninguém na tela, e o
     * passo final, em que a câmera recua e revela todo mundo lado a lado. O
     * slide de destaques não revela nada: mostra a grade inteira de uma vez.
     */
    public function steps(): int
    {
        return $this->stage === PromotionStage::Promoted
            ? 2 + (count($this->cards) * 3)
            : 0;
    }
}
