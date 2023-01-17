<?php

namespace Heart\Character\Infrastructure\Repositories;

use Heart\Character\Domain\Entities\CharacterEntity;
use Heart\Character\Domain\Repositories\CharacterRepository;
use Heart\Character\Infrastructure\Models\Character;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CharacterEloquentRepository implements CharacterRepository
{
    public function paginate(int $perPage = 10): LengthAwarePaginator
    {
        return Character::paginate($perPage);
    }

    public function findById(string $providerId): CharacterEntity
    {
        $model = Character::find($providerId);
        return CharacterEntity::make($model->toArray());
    }

    public function claimDailyBonus(CharacterEntity $character)
    {
        return Character::find($character->id)
            ->update(['daily_bonus_claimed_at' => now()]);
    }

    public function updateReputation(CharacterEntity $character)
    {
        return Character::find($character->id)
            ->update(['reputation' => $character->reputation->getPoints()]);
    }
}
