<?php

namespace Heart\Character\Domain\Entities;

use DateInterval;
use DateTime;
use Illuminate\Support\Facades\Date;

class DailyReward
{
    public DateTime $claimedAt;

    public function __construct(string $claimedAt)
    {
        $dateTime = new DateTime($claimedAt);
        $this->claimedAt = $dateTime;
    }

    public function canClaim(): bool
    {
        $dateTimeInterval = DateInterval::createFromDateString("+1 day");
        $oneDayLater = $this->claimedAt->add($dateTimeInterval);
        return $this->claimedAt >= $oneDayLater;
    }

    public function minutesUntilClaim(): int
    {
        return 1;
    }

}
