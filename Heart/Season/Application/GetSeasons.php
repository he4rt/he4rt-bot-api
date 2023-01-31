<?php

namespace Heart\Season\Application;

use Heart\Season\Domain\Repositories\SeasonRepository;

class GetSeasons
{
    public function __construct(private readonly SeasonRepository $repository)
    {
    }

    public function handle(): array
    {
        return $this->repository->getAll();
    }
}
