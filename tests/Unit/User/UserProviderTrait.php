<?php

namespace Tests\Unit\User;

use Heart\User\Domain\Entities\UserEntity;
use Heart\User\Domain\ValueObjects\UserId;
use Heart\User\Domain\ValueObjects\UserName;

trait UserProviderTrait
{
    public function validUserPayload(array $fields = []): array
    {
        return [
            'id' => 12,
            'username' => new UserName('canhassi'),
            'isDonator' => false,
            ...$fields
        ];
    }

    public function validUserEntity(): userEntity
    {
        return UserEntity::make($this->validUserPayload());
    }
}
