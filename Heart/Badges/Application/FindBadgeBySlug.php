<?php

namespace Heart\Badges\Application;

use Heart\Badges\Domain\Entities\BadgeEntity;
use Heart\Badges\Domain\Repositories\BadgeRepository;

class FindBadgeBySlug
{
    public function __construct(private readonly BadgeRepository $badgeRepository)
    {
    }

    public function handle(string $badgeSlug): BadgeEntity
    {
        return $this->badgeRepository->findBySlug($badgeSlug);
    }
}
