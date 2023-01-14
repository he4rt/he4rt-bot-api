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
        $dateTime = DateTime::createFromFormat("Y-m-d H:i:s", $claimedAt);
        $this->claimedAt = $dateTime;
    }

    public function canClaim(): bool
    {

        $dateTimeInterval = DateInterval::createFromDateString('1 day');
        dump($this->claimedAt->format('Y-m-d H:i:s'));

        $oneDayLater = (clone $this->claimedAt)->add($dateTimeInterval);
        $now = new DateTime(date('Y-m-d H:i:s'));
        dump($oneDayLater->format('Y-m-d H:i:s'), $now);
        return $oneDayLater > $now;
    }

    public function minutesUntilClaim(): int
    {
        return 1;
    }

}
