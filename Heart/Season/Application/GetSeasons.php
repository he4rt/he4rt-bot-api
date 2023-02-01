<?php

namespace Heart\Season\Application;

use Heart\Season\Domain\Collections\SeasonCollection;
use Heart\Season\Domain\Repositories\SeasonRepository;

class GetSeasons
{
    public function __construct(private readonly SeasonRepository $repository)
    {
    }

    public function handle(): SeasonCollection
    {
        return $this->repository->getAll();
    }
}
