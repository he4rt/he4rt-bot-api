<?php

namespace Heart\Character\Application;

use Heart\Badges\Application\FindBadgeBySlug;
use Heart\Character\Domain\Actions\PersistClaimedBadge;
use Heart\Provider\Application\FindProvider;

class ClaimCharacterBadge
{
    public function __construct(
        private readonly PersistClaimedBadge $claimBadge,
        private readonly FindProvider $findProvider,
        private readonly FindCharacterIdByUserId $findCharacter,
        private readonly FindBadgeBySlug $findBadgeBySlug,
    ) {
    }

    public function handle(string $provider, string $providerId, string $badgeSlug): void
    {
        $providerEntity = $this->findProvider->handle($provider, $providerId);
        $characterId = $this->findCharacter->handle($providerEntity->userId);
        $badgeEntity = $this->findBadgeBySlug->handle($badgeSlug);

        $this->claimBadge->handle($characterId, $badgeEntity->id);
    }
}
