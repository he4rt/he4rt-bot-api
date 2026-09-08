<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Retrospectives\Support;

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Quem pode entrar no slide da tag: só quem tem conta vinculada numa plataforma
 * que a retrospectiva mede.
 *
 * Barrar na escolha é honesto; deixar escolher e sumir o cartão na composição não é.
 */
final readonly class PromotablePeople
{
    /**
     * @return array<string, string> userId => label
     */
    public static function search(string $term): array
    {
        return User::query()
            ->whereIn('id', self::linkedUserIds())
            ->where(
                fn (\Illuminate\Contracts\Database\Query\Builder $query) => $query
                    ->where('username', 'ilike', '%'.$term.'%')
                    ->orWhere('name', 'ilike', '%'.$term.'%'),
            )
            ->orderBy('username')
            ->limit(20)
            ->get(['id', 'name', 'username'])
            ->mapWithKeys(fn (User $user): array => [$user->id => self::label($user)])
            ->all();
    }

    public static function labelFor(?string $userId): ?string
    {
        // O id vem do jsonb, que não tem tipo: um valor que não seja UUID faria o
        // Postgres abortar a query inteira (22P02), derrubando o inspector por
        // causa de uma linha corrompida.
        if ($userId === null || Str::isUuid($userId) === false) {
            return null;
        }

        $user = User::query()->find($userId, ['id', 'name', 'username']);

        return $user instanceof User ? self::label($user) : null;
    }

    /**
     * Versão em lote do labelFor, para o select múltiplo: uma query para a
     * lista inteira, e ids que não são UUID saem antes de chegar ao Postgres.
     *
     * @param  array<array-key, mixed>  $userIds
     * @return array<string, string> userId => label
     */
    public static function labelsFor(array $userIds): array
    {
        $valid = array_values(array_filter($userIds, static fn (mixed $id): bool => is_string($id) && Str::isUuid($id)));

        if ($valid === []) {
            return [];
        }

        return User::query()
            ->whereIn('id', $valid)
            ->get(['id', 'name', 'username'])
            ->mapWithKeys(fn (User $user): array => [$user->id => self::label($user)])
            ->all();
    }

    public static function label(User $user): string
    {
        return $user->name.' (@'.$user->username.')';
    }

    /**
     * `model_id` é `character varying` e `users.id` é `uuid`: sem o cast explícito
     * o Postgres recusa a comparação (42883). Por isso a subquery, e não um
     * `whereHas('providers')`, que compararia as colunas cruas.
     *
     * @return Builder<ExternalIdentity>
     */
    private static function linkedUserIds(): Builder
    {
        return ExternalIdentity::query()
            ->whereNull('disconnected_at')
            ->where('model_type', (new User)->getMorphClass())
            ->whereIn('provider', [IdentityProvider::Discord->value, IdentityProvider::GitHub->value])
            ->selectRaw('model_id::uuid');
    }
}
