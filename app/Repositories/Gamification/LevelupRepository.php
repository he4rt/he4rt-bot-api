<?php


namespace App\Repositories\Gamification;


use App\Models\User;

class LevelupRepository
{
    /**
     * @var User
     */
    private $model;

    public function __construct()
    {
        $this->model = new User();
    }
    private function fetchExpTableLevel($currentLevel)
    {
        return \DB::table('experience_table')->find($currentLevel);
    }

    private function generateExp($isDonator)
    {
        return rand(50, 100) * ($isDonator ? 2 : 1);
    }

    public function handle(string $discordId, $isDonator): array
    {
        $this->model = $this->model->where('discord_id', $discordId)->first();
        $this->model->updateExp($this->generateExp($isDonator));
        $this->model = $this->levelUp($this->model);

        return $this->model->only(['is_levelup', 'level']);
    }



    public function levelUp(User $model): User
    {
        $result = $this->fetchExpTableLevel($model->level);
        if ($model->current_exp >= $result->required) {
            $model->levelUp();
            $model->is_levelup = true;
        } else {
            $model->is_levelup = false;
        }
        $model->required_exp = $result->required;

        return $model;
    }


}
