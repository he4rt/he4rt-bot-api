<?php

namespace Heart\User\Application;

use Heart\User\Domain\Repositories\UserRepository;

class UpdateProfile
{
    public function __construct(
        private readonly FindProfile $findProfile,
        private readonly UserRepository $userRepository
    ) {
    }

    public function handle(string $value, array $payload): void
    {

        $profileEntity = $this->findProfile->handle($value);
        $profileEntity->informationEntity->update($payload['info']);

        $this->userRepository->updateProfile($profileEntity);
    }
}
