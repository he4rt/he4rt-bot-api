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

    public function findById(string $characterId): CharacterEntity
    {
        return CharacterEntity::make(
            Character::find($characterId)->toArray()
        );
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

    public function findByUserId(string $userId): CharacterEntity
    {
        return CharacterEntity::make(
            Character::where('user_id', $userId)->first()->toArray()
        );
    }

    public function updateExperience(CharacterEntity $character)
    {
        return Character::query()
            ->find($character->id)
            ->update(['experience' => $character->level->getExperience()]);
    }
}
