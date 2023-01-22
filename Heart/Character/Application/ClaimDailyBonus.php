<?php

namespace Heart\Character\Application;

use Heart\Character\Domain\Actions\PersistDailyBonus;
use Heart\Provider\Application\FindProvider;

class ClaimDailyBonus
{
    public function __construct(
        private readonly PersistDailyBonus $dailyBonus,
        private readonly FindProvider $findProvider,
        private readonly FindCharacterIdByUserId $findCharacter,
    ) {
    }

    public function handle(string $provider, string $providerId): void
    {
        $providerEntity = $this->findProvider->handle($provider, $providerId);

        $characterId = $this->findCharacter->handle($providerEntity->userId);

        $this->dailyBonus->handle($characterId);
    }
}
