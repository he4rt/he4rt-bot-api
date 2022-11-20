<?php

namespace App\Repositories\Users;

use App\Models\User\User;

class UsersRepository
{
    public function create(string $discordId): User
    {
        return User::query()->create([
            'discord_id' => $discordId,
            'level' => 1
        ]);
    }

    public function findById(string $discordId): User
    {
        return User::where('discord_id', $discordId)->first();
    }

    public function update(string $discordId, array $payload): User
    {
        $model = $this->findById($discordId);
        $model->update($payload);

        return $model->refresh();
    }

    public function delete(string $discordId): ?bool
    {
        return $this->findById($discordId)->delete();
    }
}
