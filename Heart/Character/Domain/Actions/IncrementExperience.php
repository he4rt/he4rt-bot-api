<?php

namespace Heart\Character\Domain\Actions;

use Heart\Character\Domain\Entities\CharacterEntity;
use Heart\Character\Domain\Repositories\CharacterRepository;

class IncrementExperience
{
    public function __construct(
        private readonly CharacterRepository $characterRepository,
        private readonly FindCharacter $findCharacter
    ) {
    }

    public function handle(string $characterId, string $message): int
    {
        $characterEntity = $this->findCharacter->handle($characterId);

        return $this->getExperienceObtained($characterEntity, $message);
    }

    private function getExperienceObtained(CharacterEntity $characterEntity, string $message): int
    {
        $experienceObtained = $characterEntity->level->generateExperience($message);
        $this->characterRepository->updateExperience($characterEntity);

        return $experienceObtained;
    }
}
