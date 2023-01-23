<?php

namespace Heart\Badges\Domain\Actions;

use Heart\Badges\Domain\Repositories\BadgeRepository;

class DeleteBadge
{
    public function __construct(private readonly BadgeRepository $badgeRepository)
    {

    }

    public function handle(string $badgeId): void
    {
        $this->badgeRepository->delete($badgeId);
    }
}
