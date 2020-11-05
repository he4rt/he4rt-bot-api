<?php


namespace App\Repositories\Users;


use App\Exceptions\DailyRewardException;
use App\Models\User\User;
use Carbon\Carbon;

class UsersRepository
{
    /**
     * @var User
     */
    private $model;

    public function __construct()
    {
        $this->model = new User();
    }

    public function create(string $discordId)
    {
        return $this->model->create([
            'discord_id' => $discordId
        ]);
    }

    public function findById(string $discordId)
    {
        return $this->model->where('discord_id', $discordId)->first();
    }

    public function update(string $discordId, array $payload)
    {
        $this->model = $this->findById($discordId);
        $this->model->update($payload);

        return $this->model;
    }

    public function delete(string $discordId)
    {
        $this->findById($discordId)->delete();
        return true;
    }

    public function dailyPoints(string $discordId, bool $isDonator)
    {
        $this->model = $this->findById($discordId);

        if (!$this->validateReedemDailyPoints($this->model->daily)) {
            $time = Carbon::parse($this->model->daily)->locale('pt_BR')->diffForHumans();
            throw new DailyRewardException($time);
        }

        $this->model->dailyPoints(
            $this->generateDailyPoints($isDonator)
        );
        return $this->model;
    }

    public function generateDailyPoints(bool $donator)
    {
        return $donator ? (rand(300, 1000) * 2) : rand(250, 500);
    }

    private function validateReedemDailyPoints($lastReedem): bool
    {
        if (!$lastReedem) {
            return true;
        }

        return Carbon::parse($lastReedem)->isPast();
    }


}
