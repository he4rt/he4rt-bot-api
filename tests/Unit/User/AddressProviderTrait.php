<?php

namespace Tests\Unit\User;

use Heart\User\Domain\Entities\AddressEntity;

trait AddressProviderTrait
{
    public function validAddressPayload(array $fields = []): array
    {
        return [
            'id' => 'canhassi-id',
            'country' => 'Brazil',
            'state' => 'São Paulo',
            'city' => 'São Paulo',
            'zip_code' => '12121212',
            ...$fields,
        ];
    }

    public function validAddressEntity(): AddressEntity
    {
        return AddressEntity::make($this->validAddressPayload());
    }
}
