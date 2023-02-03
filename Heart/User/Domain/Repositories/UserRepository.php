<?php

namespace Heart\User\Domain\Repositories;

use Heart\Shared\Domain\Paginator;
use Heart\User\Domain\Entities\ProfileEntity;
use Heart\User\Domain\Entities\UserEntity;
use Heart\User\Domain\Exceptions\UserEntityException;

interface UserRepository
{
    public function paginated(int $perPage = 15): Paginator;

    public function get(): array;

    /** @throws UserEntityException */
    public function find(string $id): UserEntity;

    public function findByUsername(string $username): ?UserEntity;

    public function findProfile(string $userId): ProfileEntity;

    public function createUser(string $username): UserEntity;
}
