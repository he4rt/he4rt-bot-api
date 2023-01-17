<?php

namespace Heart\Provider\Infrastructure\Repositories;


use Heart\Authentication\OAuth\Domain\DTO\OAuthAccessDTO;
use Heart\Provider\Domain\Repositories\TokenRepository;
use Heart\Provider\Infrastructure\Models\Token;

class TokenEloquentRepository implements TokenRepository
{
    public function create(string $providerId, OAuthAccessDTO $credentials): Token
    {
        return Token::create([
            'provider_id' => $providerId,
            ...$credentials->toDatabase()
        ]);
    }
}
