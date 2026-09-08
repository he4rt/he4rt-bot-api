<?php

declare(strict_types=1);

namespace He4rt\Community\Retrospective\DTOs;

use Carbon\CarbonImmutable;

/**
 * Uma pessoa do deck em termos que TODA fonte entende: quem ela é para exibição
 * mais as contas que ela tem em cada plataforma.
 *
 * Existe para o slide de promoções não precisar de refs prefixados como as
 * exclusions ("member:", "actor:"). Ali o prefixo é a única forma de a fonte
 * reconhecer o que é seu numa lista achatada; aqui a chave já é o provider, e
 * inventar prefixo faria o domínio decorar o vocabulário de cada fonte. Cada
 * fonte pede a conta do provider que ela lê e devolve lista vazia se não houver.
 */
final readonly class PersonIdentity
{
    /**
     * @param  array<string, PersonAccount>  $accounts  provider => conta
     * @param  CarbonImmutable|null  $memberSince  o mais antigo entre entrar no Discord e criar a conta no site
     */
    public function __construct(
        public string $userId,
        public string $name,
        public string $username,
        public string $avatar,
        public array $accounts = [],
        public ?CarbonImmutable $memberSince = null,
    ) {}

    public function account(string $provider): ?PersonAccount
    {
        return $this->accounts[$provider] ?? null;
    }

    /**
     * Sem conta nenhuma não há número para mostrar, e o deck não exibe cartão sem
     * prova — o painel barra a pessoa antes disso (só oferece na busca quem tem
     * conta), e isto é a rede de segurança do lado do domínio.
     */
    public function hasAccounts(): bool
    {
        return $this->accounts !== [];
    }
}
