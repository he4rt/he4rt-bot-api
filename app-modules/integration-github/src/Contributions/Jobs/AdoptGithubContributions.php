<?php

declare(strict_types=1);

namespace He4rt\IntegrationGithub\Contributions\Jobs;

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\IntegrationGithub\Contributions\TrackGithubContribution;
use He4rt\IntegrationGithub\Models\GithubContribution;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Conectar o GitHub resgata o que já estava no lake. É o que torna "descarta na
 * entrada" um adiamento e não uma perda.
 *
 * ExternalIdentityConnected dispara a cada login OAuth, não só na primeira conexão,
 * por isso o trabalho vai para a fila — e a idempotência do external_ref faz da
 * repetição um no-op barato.
 */
final class AdoptGithubContributions implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly string $identityId) {}

    public function handle(TrackGithubContribution $producer): void
    {
        $identity = ExternalIdentity::query()->find($this->identityId);

        if (!$identity instanceof ExternalIdentity || $identity->provider !== IdentityProvider::GitHub) {
            return;
        }

        $login = mb_strtolower((string) ($identity->metadata['username'] ?? ''));
        // actor_id é bigint; external_account_id é texto livre. Comparar sem checar
        // faz o Postgres abortar a query inteira em vez de simplesmente não casar.
        $accountId = $identity->external_account_id;
        $numericAccountId = is_string($accountId) && ctype_digit($accountId) ? (int) $accountId : null;

        if ($numericAccountId === null && $login === '') {
            return;
        }

        GithubContribution::query()
            ->where(function (Builder $query) use ($numericAccountId, $login): void {
                if ($numericAccountId !== null) {
                    $query->where('actor_id', $numericAccountId);
                }

                if ($login !== '') {
                    $query->orWhereRaw('lower(actor_login) = ?', [$login]);
                }
            })
            ->chunkById(500, function ($contributions) use ($producer): void {
                foreach ($contributions as $contribution) {
                    $producer->adopt($contribution);
                }
            });
    }
}
