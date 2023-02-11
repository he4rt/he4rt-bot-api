<?php

namespace Heart\Ranking\Domain\Actions;

use Heart\Ranking\Domain\Repositories\RankingRepository;
use Heart\Shared\Domain\Paginator;

class RankingByLevel
{
    public function __construct(
        private readonly RankingRepository $rankingRepository
    ) {
    }

    public function handle(): Paginator
    {
        return $this->rankingRepository->rankingByLevel();
    }
}
