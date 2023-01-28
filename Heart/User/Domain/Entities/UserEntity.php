<?php

namespace Heart\User\Domain\Entities;

use Exception;
use Heart\User\Domain\Exceptions\UserEntityException;
use Heart\User\Domain\ValueObjects\UserId;
use Heart\User\Domain\ValueObjects\UserName;

class UserEntity
{
    public function __construct(
        public readonly string $id,
        public readonly UserName $name,
        public readonly bool $isDonator,
    ) {
    }
    /** @throws UserEntityException */
    public static function make(array $payload): self
    {
        try {
            return new self(
                id: $payload['id'],
                name: $payload['username'],
                isDonator: $payload['isDonator']
            );
        } catch (Exception $e) {
            throw UserEntityException::failedToCreateEntity();
        }

    }

    /** @throws UserEntityException */
    public static function fromArray(array $user): self
    {
        return new UserEntity(
            id: $user['id'],
            name: new UserName($user['username']),
            isDonator: $user['is_donator']
        );
    }
}
