<?php

namespace App\Actions\Gamefication\Season;

use App\Models\Gamefication\Season;
use App\Repositories\Gamification\SeasonRepository;

class CurrentSeason
{
    private SeasonRepository $seasonRepository;

    public function __construct(SeasonRepository $repository)
    {
        $this->seasonRepository = $repository;
    }

    /**
     * Retorna a temporada ativa dentro do sistema de gameficação
     *
     * @return Season
     */
    public function handle(): Season
    {
        return $this->seasonRepository->getCurrentSeason();
    }
}
