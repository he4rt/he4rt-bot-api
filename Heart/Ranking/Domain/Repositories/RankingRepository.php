<?php

namespace Heart\Ranking\Domain\Repositories;

use Heart\Shared\Domain\Paginator;

interface RankingRepository
{
    public function rankingByLevel(): Paginator;
}
