<?php

namespace Heart\Integrations\Twitch\OAuth\Domain\DTO;

use Heart\Authentication\OAuth\Domain\DTO\OAuthAccessDTO;

class TwitchOAuthAccessDTO extends OAuthAccessDTO
{

    public static function make(array $payload): self
    {
        return new self(
            accessToken: $payload['access_token'],
            refreshToken: $payload['refresh_token'],
            expiresIn: $payload['expires_in']
        );
    }
}
