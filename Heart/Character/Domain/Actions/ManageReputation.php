<?php

namespace Heart\Character\Domain\Actions;

use Heart\Character\Domain\Repositories\CharacterRepository;

class ManageReputation
{
    public function __construct(private readonly CharacterRepository $characterRepository)
    {
    }

    public function handle(string $characterId, string $type): void
    {
        $character = $this->characterRepository->findById($characterId);
        $character->reputation->handleReputation($type);

        $this->characterRepository->updateReputation($character);
    }
}
