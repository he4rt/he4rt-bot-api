<?php

namespace Heart\Character\Domain\Actions;

use Heart\Character\Domain\Entities\CharacterEntity;
use Heart\Character\Domain\Repositories\CharacterRepository;

class FindCharacter
{
    public function __construct(private readonly CharacterRepository $characterRepository)
    {
    }

    public function handle(string $characterId): CharacterEntity
    {
        return $this->characterRepository->findById($characterId);
    }
}
