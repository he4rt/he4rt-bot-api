<?php

namespace Heart\Character\Domain\Actions;

use Heart\Character\Domain\Entities\CharacterEntity;
use Heart\Character\Domain\Repositories\CharacterRepository;

class GetCharacterByUserId
{
    public function __construct(private readonly CharacterRepository $characterRepository)
    {
    }

    public function handle(string $userId): CharacterEntity
    {
        return $this->characterRepository->findByUserId($userId);
    }
}
