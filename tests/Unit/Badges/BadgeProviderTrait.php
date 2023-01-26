<?php

namespace Tests\Unit\Badges;

use Heart\Badges\Domain\Entities\BadgeEntity;

trait BadgeProviderTrait
{
    public function validBadgePayload(array $fields = []): array
    {
        return [
            'id'          => 12,
            'name'        => 'canhassi',
            'description' => 'é o canhas, esqueça tudo!',
            'redeem_code' => 'he4rtDevelopers',
            'active'      => true
        ];
    }

    public function validBadgeEntity(): BadgeEntity
    {
        return BadgeEntity::make($this->validBadgePayload());
    }
}
