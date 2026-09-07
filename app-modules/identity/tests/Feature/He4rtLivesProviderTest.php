<?php

declare(strict_types=1);

use He4rt\Identity\ExternalIdentity\Enums\CredentialsType;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Enums\IdentityType;

it('expõe o provider da plataforma de lives da he4rt', function (): void {
    $provider = IdentityProvider::from('he4rt-lives');

    expect($provider)->toBe(IdentityProvider::He4rtLives)
        ->and($provider->getLabel())->not->toBeEmpty()
        ->and($provider->getColor())->not->toBeNull();
});

it('resolve o braço explícito de He4rtLives em cada match do provider', function (): void {
    $provider = IdentityProvider::He4rtLives;

    expect($provider->getType())->toBe(IdentityType::External)
        ->and($provider->getCredentialsType())->toBe(CredentialsType::OAuth2)
        ->and($provider->getApiKeyClient())->toBeNull()
        ->and($provider->getClient())->toBeNull()
        ->and($provider->getScopes())->toBeEmpty()
        ->and($provider->getMessageAdapter())->toBeNull();
});
