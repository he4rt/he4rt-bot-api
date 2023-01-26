<?php

namespace Heart\User\Domain\Actions;

use Heart\User\Domain\Entities\ProfileEntity;
use Heart\User\Domain\Repositories\UserRepository;

class GetProfile
{
    public function __construct(private readonly UserRepository $userRepository)
    {
    }

    public function handle(string $userId): ProfileEntity
    {
        return $this->userRepository->findProfile($userId);
    }
}
