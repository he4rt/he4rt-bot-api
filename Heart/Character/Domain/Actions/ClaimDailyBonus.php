<?php

namespace Heart\Character\Domain\Actions;

use Heart\Character\Domain\Exceptions\CharacterException;
use Heart\Character\Domain\Repositories\CharacterRepository;

class ClaimDailyBonus
{
    public function __construct(private readonly CharacterRepository $characterRepository)
    {
    }

    public function handle(string $providerId): void
    {
        $character = $this->characterRepository->findById($providerId);

        if (!$character->dailyReward->canClaim()) {
            throw CharacterException::alreadyClaimed($character->dailyReward);
        }

        $this->characterRepository->claimDailyBonus($character);
    }
}
