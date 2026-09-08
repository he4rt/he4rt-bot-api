<?php

declare(strict_types=1);

namespace He4rt\Community\Retrospective\Contracts;

use Carbon\CarbonImmutable;

/**
 * Quem sabe dizer quando cada conta chegou à comunidade na plataforma dela.
 *
 * É contrato pelo mesmo motivo do PersonDirectory: a data de entrada mora num
 * modelo de integração (o servidor do Discord conhece o `joined_at` de cada
 * membro), e o domínio não importa integração. A integração implementa e o
 * container costura.
 */
interface MembershipDates
{
    /**
     * @param  list<string>  $identityIds
     * @return array<string, CarbonImmutable> id da identidade => quando entrou
     */
    public function execute(array $identityIds): array;
}
