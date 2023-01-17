<?php

namespace Heart\User\Domain\Entity;

use Exception;
use Heart\User\Domain\Exceptions\UserEntityException;
use Heart\User\Domain\ValueObjects\UserId;
use Heart\User\Domain\ValueObjects\UserName;

class UserEntity
{
    public function __construct(
        private readonly UserId $id,
        private readonly UserName $name,
        private readonly bool $isDonator,
    ) {
    }

    /** @throws UserEntityException */
    public static function fromArray(array $user): self
    {
        try {
            return new UserEntity(
                id: new UserId($user['id']),
                name: new UserName($user['name']),
                isDonator: $user['isDonator']
            );
        } catch (Exception $e) {
            throw UserEntityException::failedToCreateEntity();
        }
    }
}
