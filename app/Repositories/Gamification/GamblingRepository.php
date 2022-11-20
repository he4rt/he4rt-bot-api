<?php

namespace App\Repositories\Gamification;

use App\Models\User\User;

class GamblingRepository
{
    /**
     * @var User
     */
    private $model;

    public function __construct()
    {
        $this->model = new User();
    }

    public function findById(string $discordId)
    {
        return $this->model->where('discord_id', $discordId)->first();
    }

    public function updateMoney(string $discordId, string $operation, int $value)
    {
        $this->model = $this->findById($discordId);
        $this->model->updateMoney($operation, $value);

        return $this->model;
    }
}
