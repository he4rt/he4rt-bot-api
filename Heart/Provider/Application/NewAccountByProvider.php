<?php

namespace Heart\Provider\Application;

use Heart\Provider\Domain\DTOs\NewProviderDTO;
use Heart\Provider\Domain\Enums\ProviderEnum;
use Heart\Provider\Domain\Repositories\ProviderRepository;
use Heart\User\Domain\Repositories\UserRepository;

class NewAccountByProvider
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly ProviderRepository $providerRepository,
    ) {
    }

    public function handle(ProviderEnum $providerEnum, string $providerId, string $username)
    {
        $existentProvider = $this->providerRepository->getProvider($providerEnum->value, $providerId);

        if ($existentProvider) {
            return $existentProvider;
        }

        $userEntity = $this->userRepository->createUser($username);

        return $this->providerRepository->create($userEntity->id, new NewProviderDTO(
            provider: $providerEnum,
            providerId: $providerId
        ));
    }
}
