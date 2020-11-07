<?php


namespace App\Repositories\Gamification;


use App\Models\User\User;

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

    public function fetchExpTableLevel($currentLevel)
    {
        return \DB::table('experience_table')->find($currentLevel);
    }

    private function generateExp($isDonator)
    {
        return rand(50, 100) * ($isDonator ? 2 : 1);
    }

    public function handle(string $discordId, $isDonator, $message): array
    {
        $this->model = $this->model->where('discord_id', $discordId)->first();
        $obtainedExperience = $this->generateExp($isDonator);

        $this->model->messages()->create([
            'season_id' => env('APP_SEASON'),
            'message' => $message,
            'obtained_experience' => $obtainedExperience
        ]);
        $this->model->updateExp($obtainedExperience);
        $this->model = $this->levelUp($this->model);

        return $this->model->only(['is_levelup', 'level']);
    }


    public function levelUp(User $model): User
    {

        if ($model->current_exp >= $model->levelup_exp->required) {
            $currentLevel = $model->levelUp();
            $model->levelupLog()->create([
                'season_id' => env('APP_SEASON'),
                'level' => $currentLevel
            ]);
            $model->is_levelup = true;
        } else {
            $model->is_levelup = false;
        }
        $model->required_exp = $model->levelup_exp->required;

        return $model;
    }


}
