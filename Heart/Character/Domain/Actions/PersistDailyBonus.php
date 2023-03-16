<?php

namespace Heart\Character\Domain\Actions;

use Heart\Character\Domain\Exceptions\CharacterException;
use Heart\Character\Domain\Repositories\CharacterRepository;

class PersistDailyBonus
{
    public function __construct(private readonly CharacterRepository $characterRepository)
    {
    }

    public function handle(string $characterId): void
    {
        $character = $this->characterRepository->findById($characterId);
        if (! $character->dailyReward->canClaim()) {
            throw CharacterException::alreadyClaimed($character->dailyReward);
        }

        $this->characterRepository->claimDailyBonus($character);
    }
}
