<?php

namespace Heart\Authentication\OAuth\Application;

use GuzzleHttp\Exception\ClientException;
use Heart\Authentication\OAuth\Infrastructure\Enums\OAuthProviderEnum;
use Heart\Provider\Domain\Repositories\ProviderRepository;
use Heart\Provider\Domain\Repositories\TokenRepository;
use Illuminate\Support\Facades\Auth;
use Kingdom\Authentication\OAuth\Domain\Actions\GetOAuthUser;

final class OAuthService
{
    public function __construct(
        private GetOAuthUser          $getUserAction,
        private ProviderRepository    $providerRepository,
        private TokenRepository       $tokenRepository,
        private SubscribersRepository $subscribersRepository
    )
    {
    }

    public function handle(string $provider, string $code)
    {
        try {
            $providerUser = $this->getUserAction->handle($provider, $code);
        } catch (ClientException $e) {
            if (str_contains($e->getMessage(), 'Invalid authorization code')) {
                $OAuthProviderEnum = OAuthProviderEnum::from($provider);
                return redirect()->to($OAuthProviderEnum->getProvider()->redirectUrl());
            }

            throw $e;
        }

        $persistedProvider = $this->providerRepository->findByProvider($providerUser);

        if ($persistedProvider) {
            $this->tokenRepository->create($persistedProvider->getKey(), $providerUser->credentials);
            Auth::login($persistedProvider->subscriber);
            return;
        }

        $subscriberId = Auth::check()
            ? Auth::id()
            : $this->subscribersRepository->create(SubscriberDTO::makeFromOAuth($providerUser))->getKey();

        $persistedProvider = $this->providerRepository->create($subscriberId, $providerUser);
        $this->tokenRepository->create($persistedProvider->getKey(), $providerUser->credentials);

        Auth::login($persistedProvider->subscriber);
    }
}
