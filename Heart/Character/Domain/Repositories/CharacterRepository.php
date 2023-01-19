<?php

namespace Heart\Character\Domain\Repositories;

use Heart\Character\Domain\Entities\CharacterEntity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CharacterRepository
{
    public function paginate(int $perPage = 10): LengthAwarePaginator;

    public function findById(string $characterId): CharacterEntity;

    public function findByUserId(string $userId): CharacterEntity;

    public function claimDailyBonus(CharacterEntity $character);

    public function updateReputation(CharacterEntity $character);

    public function updateExperience(CharacterEntity $character);
}
