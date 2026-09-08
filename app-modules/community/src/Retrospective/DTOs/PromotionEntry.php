<?php

declare(strict_types=1);

namespace He4rt\Community\Retrospective\DTOs;

use He4rt\Community\Retrospective\Enums\PromotionStage;

/**
 * Uma escolha do operador no slide da tag He4rt: QUEM aparece, em que estágio e
 * por quê. Mora no deck_config porque é curadoria — mas curadoria do tipo que
 * mexe no dado, como as exclusions: trocar a pessoa troca os números exibidos,
 * então exige republicar.
 *
 * Carrega só o id do usuário e a copy. Nome, avatar e métricas são derivados na
 * composição: guardá-los aqui congelaria um apelido antigo dentro da curadoria,
 * onde ninguém iria procurá-lo.
 */
final readonly class PromotionEntry
{
    public function __construct(
        public string $userId,
        public PromotionStage $stage,
        public ?string $reason = null,
    ) {}

    /**
     * @param  array<array-key, mixed>  $payload
     */
    public static function makeFromPayload(array $payload): ?self
    {
        $userId = $payload['user_id'] ?? null;

        if (!is_string($userId) || $userId === '') {
            return null;
        }

        $stage = PromotionStage::tryFrom((string) ($payload['stage'] ?? ''));

        if (!$stage instanceof PromotionStage) {
            return null;
        }

        $reason = $payload['reason'] ?? null;

        return new self($userId, $stage, is_string($reason) && $reason !== '' ? $reason : null);
    }

    /**
     * @return array{user_id: string, stage: string, reason: string|null}
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'stage' => $this->stage->value,
            'reason' => $this->reason,
        ];
    }

    /**
     * Identidade da escolha para comparar edição contra snapshot. Inclui o motivo
     * de propósito: ele é exibido junto dos números, então corrigi-lo também deixa
     * o publicado defasado.
     */
    public function signature(): string
    {
        return $this->userId.'|'.$this->stage->value.'|'.($this->reason ?? '');
    }
}
