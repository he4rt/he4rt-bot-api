<?php

namespace App\Repositories\Users;

use App\Exceptions\UserException;
use App\Models\User\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UsersRepository
{
    public function create(string $discordId): User
    {
        return User::query()->create([
            'discord_id' => $discordId,
            'level' => 1
        ]);
    }

    public function find(int $userId): User
    {
        return User::find($userId);
    }

    public function findById(string $discordId): ?User
    {
        return User::select(DB::raw("*, RANK() OVER (ORDER BY level DESC, current_exp desc) as ranking_position"))
            ->where('discord_id', $discordId)->first();
    }

    public function findByIdWithLoad(string $discordId): ?User
    {
        return User::select(DB::raw("*, RANK() OVER (ORDER BY level DESC, current_exp desc) as ranking_position"))
            ->withCount(['badges', 'messages'])
            ->with('seasonInfo')
            ->where('discord_id', $discordId)->first();
    }

    /**
     * @throws UserException
     */
    public function update(string $discordId, array $payload): User
    {
        if (!$model = $this->findById($discordId)) {
            throw UserException::discordIdNotFound($discordId);
        };

        $model->update($payload);

        return $model->refresh();
    }

    public function delete(string $discordId): ?bool
    {
        return $this->findById($discordId)->delete();
    }

    public function levelUp(int $userId, int $currentExp): void
    {
        $model = $this->find($userId);
        $model->levelUp($currentExp);
    }

    public function incrementExperience(int $userId, int $givenExperience): void
    {
        User::query()
            ->lockForUpdate()
            ->find($userId)
            ->increment('current_exp', $givenExperience);
    }

    public function attachBadge(string $discordId, int $badgeId): void
    {
        $this->findById($discordId)
            ->badges()
            ->attach($badgeId);
    }

    public function attendMeeting(int $discordId, int $meetingId): void
    {
        $this->findById($discordId)
            ->meetings()
            ->attach($meetingId, ['attend_at' => Carbon::now()]);
    }
}
