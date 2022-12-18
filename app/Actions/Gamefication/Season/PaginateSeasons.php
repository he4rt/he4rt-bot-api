<?php

namespace App\Actions\Gamefication\Season;

use App\Repositories\Gamification\SeasonRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class PaginateSeasons
{
    private SeasonRepository $seasonRepository;

    public function __construct(SeasonRepository $repository)
    {
        $this->seasonRepository = $repository;
    }

    public function handle(): LengthAwarePaginator
    {
        return $this->seasonRepository->paginate();
    }
}
