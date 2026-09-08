<?php

declare(strict_types=1);

namespace He4rt\Community\Retrospective\Actions;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use He4rt\Community\Retrospective\Contracts\MembershipDates;
use He4rt\Community\Retrospective\Contracts\PersonDirectory;
use He4rt\Community\Retrospective\DTOs\PersonAccount;
use He4rt\Community\Retrospective\DTOs\PersonIdentity;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Traduz ids de usuário nas PersonIdentity que as fontes sabem medir.
 *
 * O vínculo entre plataformas já existe no identity — um User pendura suas
 * contas em `providers` — então o slide da tag não precisa da tabela de
 * tradução que o ADR-0001 diz não existir: ele pergunta ao dono das identidades.
 *
 * Carrega todo mundo de uma vez: são poucas pessoas por deck, mas resolver uma a
 * uma dentro do loop de composição custaria duas queries por cartão.
 */
final readonly class ResolvePeople implements PersonDirectory
{
    public function __construct(private MembershipDates $membershipDates) {}

    /**
     * @param  list<string>  $userIds
     * @return array<string, PersonIdentity> id do usuário => pessoa
     */
    public function execute(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        /** @var Collection<int, User> $users */
        $users = User::query()
            // `whereNull('disconnected_at')` e não o scope `activelyConnected`: ele
            // exige access_token guardado, e só 210 das ~50 mil contas o têm (o
            // token expira e não é renovado). Para MEDIR o passado basta a conta
            // estar vinculada — token válido é requisito de quem vai CHAMAR a API.
            ->with(['providers' => static fn (Relation $query): Relation => $query->whereNull('disconnected_at')])
            ->whereIn('id', array_values(array_unique($userIds)))
            ->get();

        $accountsByUser = [];

        foreach ($users as $user) {
            $accountsByUser[$user->id] = $this->accounts($user);
        }

        $joinedAt = $this->joinedAt($accountsByUser);
        $people = [];

        foreach ($users as $user) {
            $accounts = $accountsByUser[$user->id];

            $people[$user->id] = new PersonIdentity(
                userId: $user->id,
                name: $user->name,
                username: $user->username,
                avatar: $this->avatar($user, $accounts),
                accounts: $accounts,
                memberSince: $this->memberSince($user, $accounts, $joinedAt),
            );
        }

        return $people;
    }

    /**
     * Uma conta por provider. Empate (a mesma plataforma conectada duas vezes)
     * fica com a mais recente: é a que o resto do sistema considera ativa.
     *
     * @return array<string, PersonAccount>
     */
    private function accounts(User $user): array
    {
        $accounts = [];

        foreach ($user->providers->sortBy('connected_at') as $identity) {
            $metadata = is_array($identity->metadata) ? $identity->metadata : [];

            $accounts[$identity->provider->value] = new PersonAccount(
                identityId: $identity->id,
                accountId: $identity->external_account_id,
                username: $this->stringOrNull($metadata['username'] ?? null),
                avatar: $this->stringOrNull($metadata['avatar'] ?? null),
            );
        }

        return $accounts;
    }

    /**
     * GitHub primeiro, Discord depois, o do sistema por último.
     *
     * A URL `github.com/{username}.png` usa o username da conta VINCULADA, então
     * sempre resolve para a foto real — diferente do fallback final, que monta a
     * mesma URL com o username do site e costuma devolver a imagem de erro.
     *
     * @param  array<string, PersonAccount>  $accounts
     */
    private function avatar(User $user, array $accounts): string
    {
        $github = $accounts[IdentityProvider::GitHub->value] ?? null;

        if ($github?->username !== null) {
            return sprintf('https://github.com/%s.png', $github->username);
        }

        $discord = $accounts[IdentityProvider::Discord->value] ?? null;

        if ($discord?->avatar !== null && $discord->avatar !== '') {
            return $discord->avatar;
        }

        $uploaded = $user->getFirstMediaUrl('avatar');

        if ($uploaded !== '') {
            return $uploaded;
        }

        return sprintf('https://github.com/%s.png', $user->username);
    }

    /**
     * Desde quando a pessoa está na comunidade: o que vier PRIMEIRO entre entrar
     * no servidor do Discord e criar a conta no site. A conta do site costuma vir
     * anos depois da chegada, então sem a data do Discord o "desde" mentiria.
     *
     * @param  array<string, PersonAccount>  $accounts
     * @param  array<string, CarbonImmutable>  $joinedAt  id da identidade => entrada no Discord
     */
    private function memberSince(User $user, array $accounts, array $joinedAt): ?CarbonImmutable
    {
        $dates = [];

        if ($user->created_at instanceof CarbonInterface) {
            $dates[] = $user->created_at->toImmutable();
        }

        $discord = $accounts[IdentityProvider::Discord->value] ?? null;

        if ($discord !== null && isset($joinedAt[$discord->identityId])) {
            $dates[] = $joinedAt[$discord->identityId];
        }

        if ($dates === []) {
            return null;
        }

        usort($dates, static fn (CarbonImmutable $a, CarbonImmutable $b): int => $a <=> $b);

        return $dates[0];
    }

    /**
     * Uma ida só à integração para o lote inteiro, pelo mesmo motivo do
     * carregamento dos usuários: são poucos cartões, mas perguntar pessoa a
     * pessoa custaria uma query por cartão.
     *
     * @param  array<string, array<string, PersonAccount>>  $accountsByUser
     * @return array<string, CarbonImmutable>
     */
    private function joinedAt(array $accountsByUser): array
    {
        $identityIds = [];

        foreach ($accountsByUser as $accounts) {
            $discord = $accounts[IdentityProvider::Discord->value] ?? null;

            if ($discord !== null) {
                $identityIds[] = $discord->identityId;
            }
        }

        return $this->membershipDates->execute($identityIds);
    }

    private function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
