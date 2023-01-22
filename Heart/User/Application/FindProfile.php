<?php

namespace Heart\User\Application;

use Heart\User\Domain\Repositories\UserRepository;

class FindProfile
{
    public function __construct(private readonly UserRepository $userRepository)
    {
        // via @username
        // via provider_id
    }

    public function handle(string $value)
    {
        $this->userRepository->findByUsername($value);
    }
}
