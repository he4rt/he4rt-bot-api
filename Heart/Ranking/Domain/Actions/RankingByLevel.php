<?php

namespace Heart\Ranking\Domain\Actions;

use Heart\Ranking\Domain\Repositories\RankingRepository;

class RankingByLevel
{
    public function __construct(
        private readonly RankingRepository $rankingRepository
    ) {
    }

    public function handle()
    {
        return $this->rankingRepository->rankingByLevel();
    }
}
