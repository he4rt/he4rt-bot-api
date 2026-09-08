<?php

declare(strict_types=1);

namespace He4rt\Community\Retrospective\Contracts;

use He4rt\Community\Retrospective\DTOs\PersonIdentity;

/**
 * Quem sabe transformar ids de usuário nas pessoas que as fontes medem.
 *
 * Existe como contrato porque é a única porta do slide da tag para FORA do
 * recorte: tudo o mais que o ComposePromotions toca vem das fontes por tag de
 * container, e sem esta costura o teste da orquestração precisaria de banco para
 * verificar uma regra que não é sobre banco nenhum.
 */
interface PersonDirectory
{
    /**
     * @param  list<string>  $userIds
     * @return array<string, PersonIdentity> id do usuário => pessoa
     */
    public function execute(array $userIds): array;
}
