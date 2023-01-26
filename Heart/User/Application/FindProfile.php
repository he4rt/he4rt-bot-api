<?php

namespace Heart\User\Application;

use Heart\Provider\Domain\Repositories\ProviderRepository;
use Heart\User\Application\Exceptions\ProfileException;
use Heart\User\Domain\Actions\GetProfile;
use Heart\User\Domain\Entities\ProfileEntity;
use Heart\User\Domain\Repositories\UserRepository;

class FindProfile
{
    public function __construct(
        private readonly GetProfile $profile,
        private readonly UserRepository $userRepository,
        private readonly ProviderRepository $providerRepository,
    ) {
    }

    public function handle(string $value): ProfileEntity
    {
        $userEntity = $this->userRepository->findByUsername($value);

        if ($userEntity) {
            return $this->profile->handle($userEntity->id);
        }

        $providerEntity = $this->providerRepository->findByProviderId($value);

        if ($providerEntity) {
            return $this->profile->handle($providerEntity->id);
        }

        throw ProfileException::notFound();
    }
}
