<?php

namespace Heart\Badges\Domain\Repositories;

use Heart\Badges\Domain\DTOs\NewBadgeDTO;
use Heart\Badges\Domain\Entities\BadgeEntity;

interface BadgeRepository
{

    public function create(NewBadgeDTO $badgeDTO): BadgeEntity;
}
