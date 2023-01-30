<?php

namespace Heart\Character\Domain\Entities;

use DateInterval;
use DateTime;

class DailyRewardEntity
{
    public DateTime $claimedAt;

    public function __construct(?string $claimedAt)
    {
        $dateTime = DateTime::createFromFormat('Y-m-d H:i:s', $claimedAt);
        $this->claimedAt = $dateTime;
    }

    public function canClaim(): bool
    {
        $dateTimeInterval = DateInterval::createFromDateString('1 day');
        $oneDayLater = (clone $this->claimedAt)->add($dateTimeInterval);
        $now = new DateTime(now());

        return $now > $oneDayLater;
    }

    public function minutesUntilClaim(): int
    {
        return 1;
    }
}
