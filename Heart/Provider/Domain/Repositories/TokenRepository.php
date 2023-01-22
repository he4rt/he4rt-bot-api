<?php

namespace Heart\Provider\Domain\Repositories;

use Heart\Authentication\OAuth\Domain\DTO\OAuthAccessDTO;
use Heart\Provider\Infrastructure\Models\Token;

interface TokenRepository
{
    public function create(string $providerId, OAuthAccessDTO $credentials): Token;
}
