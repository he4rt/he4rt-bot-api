<?php

namespace Heart\User\Application;

use Heart\User\Domain\Entity\UserEntity;
use Heart\User\Domain\Exceptions\UserEntityException;
use Heart\User\Domain\Repositories\UserRepository;
use Heart\User\Domain\ValueObjects\UserId;

class GetUser
{
    public function __construct(private readonly UserRepository $repository)
    {
    }

    /** @throws UserEntityException */
    public function handle(int $userId): UserEntity
    {
        return $this->repository->find(new UserId($userId));
    }
}
