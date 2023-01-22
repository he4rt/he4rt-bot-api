<?php

namespace Heart\User\Domain\Repositories;

use Heart\User\Domain\Entities\UserEntity;
use Heart\User\Domain\Exceptions\UserEntityException;
use Heart\User\Domain\ValueObjects\UserId;
use Illuminate\Http\JsonResponse;

interface UserRepository
{
    public function paginated(bool $shouldPaginate = true, ?int $perPage = null): self;

    public function get(): array;

    /** @throws UserEntityException */
    public function find(UserId $id): UserEntity;
}
