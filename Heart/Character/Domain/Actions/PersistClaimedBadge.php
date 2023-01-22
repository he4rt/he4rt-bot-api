<?php

namespace Heart\Character\Domain\Actions;

use Heart\Character\Domain\Repositories\CharacterRepository;

class PersistClaimedBadge
{
    public function __construct(private readonly CharacterRepository $characterRepository)
    {
    }

    public function handle(string $characterId, int $badgeId): void
    {
        $this->characterRepository->claimBadge($characterId, $badgeId);
    }
}
