<?php

namespace Heart\Integrations\Twitch\Subscriber\Domain\Enum;

enum SubscriptionTiersEnum: string
{
    case Tier_1 = '1000';
    case Tier_2 = '2000';
    case Tier_3 = '3000';
}
