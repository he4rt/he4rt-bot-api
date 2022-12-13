<?php

namespace App\Repositories\Gamification;

use App\Models\Gamefication\Season;
use Illuminate\Pagination\LengthAwarePaginator;

class SeasonRepository
{
    public function paginate(int $itemsPerPage = 10): LengthAwarePaginator
    {
        return Season::query()
            ->orderByDesc('starts_at')
            ->paginate($itemsPerPage);
    }

    public function getCurrentSeason(): Season
    {
        return Season::currentSeason()->first();
    }
}
