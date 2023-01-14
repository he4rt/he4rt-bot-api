<?php

namespace Heart\Integrations\Twitch\Common;

use GuzzleHttp\Client;
use Heart\Integrations\Twitch\Subscriber\Infrastructure\TwitchSubscribersClient;
use Heart\Integrations\Twitch\OAuth\Domain\TwitchOAuthService;
use Heart\Integrations\Twitch\Subscriber\Domain\TwitchSubscribersService;
use Heart\Integrations\Twitch\OAuth\Infrastructure\TwitchOAuthClient;

final class TwitchBaseClient implements TwitchService
{

    public function __construct(private readonly Client $client)
    {
    }

    public function oauth(): TwitchOAuthService
    {
        return new TwitchOAuthClient($this->client);
    }

    public function subscribers(): TwitchSubscribersService
    {
        return new TwitchSubscribersClient($this->client);
    }
}
