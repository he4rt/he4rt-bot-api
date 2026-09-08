<?php

declare(strict_types=1);

namespace He4rt\Community\Retrospective\DTOs;

/**
 * A conta de uma pessoa numa plataforma, do jeito que o identity a conhece.
 *
 * Carrega as três chaves porque cada fonte casa por uma diferente: o Discord
 * agrega por `identityId` (é a coluna que messages/voice guardam), o GitHub por
 * `accountId` (o mesmo número que `github_contributions.actor_id`), e `username`
 * é o que se mostra a um humano. Nenhuma fonte precisa saber por qual chave a
 * irmã casa.
 */
final readonly class PersonAccount
{
    public function __construct(
        public string $identityId,
        public ?string $accountId = null,
        public ?string $username = null,
        public ?string $avatar = null,
    ) {}
}
