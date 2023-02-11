<?php

namespace Heart\Ranking\Infrastructure\Repositories;

use Heart\Character\Infrastructure\Models\Character;
use Heart\Ranking\Domain\Repositories\RankingRepository;
use Heart\Shared\Domain\Paginator;
use Heart\Shared\Infrastructure\Paginator as PaginatorConcrete;

class RankingEloquentRepository implements RankingRepository
{
    public function rankingByLevel(): Paginator
    {
        $ranking = Character::with(['user'])
            ->orderByDesc('experience')
            ->paginate(10);
        return PaginatorConcrete::paginate($ranking);
    }
}
