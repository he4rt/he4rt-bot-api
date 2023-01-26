<?php

namespace Tests\Unit\Character;

use Heart\Provider\Domain\Entities\ProviderEntity;

trait ProviderProviderTrait
{
    public function validProviderPayload(array $fields = []): array
    {
        return [
            'id' => 'canhassi-id',
            'user_id' => 'user-id',
            'provider' => 'he4rt',
            'provider_id' => 'provider-id',
            'email' => 'canhas@gmail.com',
            ...$fields
        ];
    }

    public function validProviderEntity(): ProviderEntity
    {
        return ProviderEntity::make($this->validProviderPayload());
    }
}
