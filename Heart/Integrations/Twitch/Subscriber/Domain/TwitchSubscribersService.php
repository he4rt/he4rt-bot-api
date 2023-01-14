<?php

namespace Heart\Integrations\Twitch\Subscriber\Domain;

use Heart\Authentication\OAuth\Domain\DTO\OAuthAccessDTO;

interface TwitchSubscribersService
{
    public function getSubscriptionState(OAuthAccessDTO $dto, string $twitchId, string $channelId);
}
