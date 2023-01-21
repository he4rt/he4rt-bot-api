<?php

namespace Heart\Badges\Domain\Actions;

use Heart\Badges\Domain\DTOs\NewBadgeDTO;
use Heart\Badges\Domain\Entities\BadgeEntity;
use Heart\Badges\Domain\Repositories\BadgeRepository;

class PersistBadge
{
    public function __construct(private readonly BadgeRepository $badgeRepository)
    {
    }

    public function handle(array $payload): BadgeEntity
    {
        return $this->badgeRepository->create(NewBadgeDTO::make($payload));
    }
}
