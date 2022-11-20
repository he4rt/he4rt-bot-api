<?php

namespace App\Repositories\Gamification;

use App\Models\Gamification\Season;

class SeasonRepository
{
    /**
     * @var Season
     */
    private $model;

    private $paginate = 10;

    public function __construct()
    {
        $this->model = new Season();
    }

    public function paginateSeasons()
    {
        return $this->model->paginate($this->paginate);
    }

    public function fetchActiveSeason()
    {
        return $this->model->where('status', 1)->first();
    }

    public function createNewSeason($name, $duration)
    {
        return $this->model->create([
            'name' => $name,
            'duration' => $duration
        ]);
    }

    public function fetchSeason(int $seasonId)
    {
        return $this->model->find($seasonId);
    }

    public function updateSeason(int $seasonId, $name, $duration)
    {
        return $this->model
            ->find($seasonId)
            ->update([
                'name' => $name,
                'duration' => $duration
            ]);
    }

    public function deleteSeason(int $seasonId)
    {
        return $this->model
            ->find($seasonId)
            ->delete();
    }

    public function wipeSeason($wipeKey): bool
    {
        if (env('WIPE_KEY') != $wipeKey) {
            return false;
        }



        return true;
    }
}
