<?php

namespace Heart\Badges\Infrastructure\Repositories;

use Heart\Badges\Domain\DTOs\NewBadgeDTO;
use Heart\Badges\Domain\Entities\BadgeEntity;
use Heart\Badges\Domain\Repositories\BadgeRepository;
use Heart\Badges\Infrastructure\Model\Badge;

class BadgeEloquentRepository implements BadgeRepository
{
    public function __construct(private readonly Badge $model)
    {
    }

    public function create(NewBadgeDTO $badgeDTO): BadgeEntity
    {
        $model = $this->model->newQuery()->create($badgeDTO->jsonSerialize());
        return BadgeEntity::make($model->toArray());
    }

    public function findBySlug(string $badgeSlug): BadgeEntity
    {
        $model = $this->model
            ->newQuery()
            ->where('redeem_code', $badgeSlug)
            ->firstOrFail();

        return BadgeEntity::make($model->toArray());
    }
}
