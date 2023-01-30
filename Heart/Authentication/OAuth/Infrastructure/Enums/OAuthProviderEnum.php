<?php

namespace Heart\Authentication\OAuth\Infrastructure\Enums;

use Heart\Authentication\OAuth\Domain\Interfaces\OAuthClientContract;
use Heart\Integrations\Twitch\OAuth\Domain\TwitchOAuthService;

enum OAuthProviderEnum: string
{
    case Twitch = 'twitch';

    public function getProvider(): OAuthClientContract
    {
        return match ($this) {
            self::Twitch => app(TwitchOAuthService::class)
        };
    }
}
