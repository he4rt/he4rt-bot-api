<?php

namespace Heart\Season\Application;

use Heart\Season\Domain\Entities\SeasonEntity;
use Heart\Season\Domain\Repositories\SeasonRepository;

class GetCurrentSeason
{
    public function __construct(private readonly SeasonRepository $repository)
    {
    }

    public function handle(): SeasonEntity
    {
        return $this->repository->getCurrent();
    }
}
