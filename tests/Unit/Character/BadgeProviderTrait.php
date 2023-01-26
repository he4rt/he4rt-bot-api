<?php

namespace Tests\Unit\Character;

use Heart\Badges\Domain\Entities\BadgeEntity;

trait BadgeProviderTrait
{
    public function validBadgePayload(array $fields = []): array
    {
        return [
            'id' => 12,
            'name' => 'canhassi',
            'description' => 'é o canhas, esqueça tudo!',
            'image_url' => 'canhas-fez-2-arquivos-iguais.jpeg',
            'redeem_code' => 'he4rtDevelopers',
            'active' => true
        ];
    }

    public function validBadgeEntity(): BadgeEntity
    {
        return BadgeEntity::make($this->validBadgePayload());
    }
}
