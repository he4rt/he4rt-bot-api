<?php

namespace Heart\User\Domain\Repositories;

use Heart\Shared\Domain\Paginator;
use Heart\User\Domain\Entities\UserEntity;
use Heart\User\Domain\Exceptions\UserEntityException;
use Heart\User\Domain\ValueObjects\UserId;

interface UserRepository
{
    public function paginated(int $perPage = 15): Paginator;

    public function get(): array;

    /** @throws UserEntityException */
    public function find(UserId $id): UserEntity;

    public function findByUsername(string $username): UserEntity;
}
