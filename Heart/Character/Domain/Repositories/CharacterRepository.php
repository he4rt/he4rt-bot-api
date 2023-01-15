<?php

namespace Heart\Character\Domain\Repositories;

use Heart\Character\Domain\Entities\CharacterEntity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CharacterRepository
{
    public function paginate(int $perPage = 10): LengthAwarePaginator;

    public function findById(string $providerId): CharacterEntity;

    public function claimDailyBonus(CharacterEntity $character);

    public function updateReputation(CharacterEntity $character);
}
