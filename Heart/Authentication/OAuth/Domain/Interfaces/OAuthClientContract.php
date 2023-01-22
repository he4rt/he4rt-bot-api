<?php

namespace Heart\Authentication\OAuth\Domain\Interfaces;

use Heart\Authentication\OAuth\Domain\DTO\OAuthAccessDTO;
use Heart\Authentication\OAuth\Domain\DTO\OAuthUserDTO;

interface OAuthClientContract
{
    public function redirectUrl(): string;

    public function auth(string $code): OAuthAccessDTO;

    public function getAuthenticatedUser(OAuthAccessDTO $credentials): OAuthUserDTO;
}
