<?php

namespace Heart\Integrations\Twitch\Common;

use Heart\Integrations\Twitch\OAuth\Domain\TwitchOAuthService;
use Heart\Integrations\Twitch\Subscriber\Domain\TwitchSubscribersService;

interface TwitchService
{
    public function oauth(): TwitchOAuthService;

    public function subscribers(): TwitchSubscribersService;
}
