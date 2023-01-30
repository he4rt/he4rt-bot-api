<?php

namespace Heart\Badges\Application;

use Heart\Badges\Domain\Actions\PersistBadge;
use Heart\Badges\Domain\DTOs\NewBadgeDTO;
use Heart\Badges\Domain\Entities\BadgeEntity;

class CreateBadge
{
    public function __construct(private readonly PersistBadge $persistBadge)
    {
    }

    public function handle(array $payload): BadgeEntity
    {
        $newBadgeDTO = NewBadgeDTO::make($payload);

        return $this->persistBadge->handle($newBadgeDTO);
    }
}
