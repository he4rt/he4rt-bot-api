<?php

declare(strict_types=1);

namespace He4rt\IntegrationGithub\Contributions\Listeners;

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Events\ExternalIdentityConnected;
use He4rt\IntegrationGithub\Contributions\Jobs\AdoptGithubContributions;

/**
 * O job carrega o id da identidade no construtor, então não serve de listener
 * direto — o container não teria como instanciá-lo. Esta casca resolve isso e
 * mantém a regra de provider num lugar só.
 */
final class QueueContributionAdoption
{
    public function handle(ExternalIdentityConnected $event): void
    {
        if ($event->identity->provider !== IdentityProvider::GitHub) {
            return;
        }

        dispatch(new AdoptGithubContributions($event->identity->id));
    }
}
