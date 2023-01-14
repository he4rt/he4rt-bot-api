<?php

namespace Tests\Unit\Character;

use Heart\Character\Domain\Entities\CharacterEntity;

trait CharacterProviderTrait
{
    public function validCharacterPayload(array $fields = []): array
    {
        return [
            'id' => 1,
            'user_id' => 1,
            'experience' => 500,
            'daily_bonus_claimed_at' => now()->format('Y-m-d H:i:s'),
            ...$fields
        ];
    }

    public function validCharacterEntity(array $fields = [])
    {
        return CharacterEntity::make($this->validCharacterPayload());
    }

}
