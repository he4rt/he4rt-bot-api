<?php

declare(strict_types=1);

namespace He4rt\IntegrationGithub\Contributions;

use He4rt\Activity\Tracking\Enums\AttributionMethod;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\IntegrationGithub\Models\GithubContribution;
use Illuminate\Database\Eloquent\Builder;

/**
 * Casa uma contribuição do lake com a identidade GitHub conectada do autor.
 *
 * O actor_id é exato e vale para cinco dos seis tipos. Commit vindo do webhook
 * chega sem id — o payload de push do GitHub não manda o id numérico por commit —
 * então o login é fallback obrigatório, não conveniência. Login ambíguo devolve
 * null: errar o dono é pior que não registrar.
 */
final class ResolveContributorIdentity
{
    /**
     * @return array{identity: ExternalIdentity, attributed_by: AttributionMethod}|null
     */
    public function handle(GithubContribution $contribution): ?array
    {
        $byActorId = $contribution->actor_id !== null
            ? $this->connectedIdentities()
                ->where('external_account_id', (string) $contribution->actor_id)
                ->first()
            : null;

        if ($byActorId instanceof ExternalIdentity) {
            return ['identity' => $byActorId, 'attributed_by' => AttributionMethod::ExternalId];
        }

        $login = mb_strtolower(mb_trim($contribution->actor_login));

        if ($login === '') {
            return null;
        }

        $candidates = $this->connectedIdentities()
            ->whereRaw("lower(metadata->>'username') = ?", [$login])
            ->limit(2)
            ->get();

        if ($candidates->count() !== 1) {
            return null;
        }

        return ['identity' => $candidates->first(), 'attributed_by' => AttributionMethod::Handle];
    }

    /**
     * @return Builder<ExternalIdentity>
     */
    private function connectedIdentities(): Builder
    {
        return ExternalIdentity::query()
            ->where('provider', IdentityProvider::GitHub)
            ->activelyConnected();
    }
}
