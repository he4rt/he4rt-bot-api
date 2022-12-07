<?php

namespace App\Actions\Gamefication\Season;

use App\Models\Gamefication\Season;
use App\Repositories\Gamification\SeasonRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class CurrentSeason
{
    private SeasonRepository $seasonRepository;

    public function __construct(SeasonRepository $repository)
    {
        $this->seasonRepository = $repository;
    }

    public function handle(): Season
    {
        return $this->seasonRepository->getCurrentSeason();
    }
}
