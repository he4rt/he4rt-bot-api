<?php

namespace App\Actions\Gamefication\Season;

use App\Models\Gamefication\Season;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class FinishSeason
{
    public function __construct()
    {
        $this->currentSeason = Season::query()->currentSeason()->first();
    }

    public function handle()
    {
        DB::transaction(function () {
            User::query()
                ->select(DB::raw("*, RANK() OVER (ORDER BY level DESC) as ranking_position"))
                ->where('level', '>=', 3)
                ->orderBy('id')->chunk(
                    500,
                    fn(Collection $users) => $this->handleUsers($users)
                );
        });
    }

    private function handleUsers(Collection $users)
    {
        /** @var User $user */
        foreach ($users as $user) {
            $messagesCount = $this->getMessagesCount($user);
            $badgesCount = $this->getBadgesCount($user);
            $meetingsCount = $this->getMeetingsCount($user);
            $attributes = [
                'season_id' => $this->currentSeason->getKey(),
                'level' => $user->level,
                'experience' => $user->current_exp,
                'messages_count' => $messagesCount,
                'ranking_position' => $user->ranking_position,
                'meetings_count' => $meetingsCount,
                'badges_count' => $badgesCount
            ];
            $user->seasonInfo()->create($attributes);
            $user->wipe();
        }
    }

    public function getMessagesCount(User $user): int
    {
        return $user->messages()
            ->whereBetween('created_at', [
                $this->currentSeason->starts_at,
                $this->currentSeason->ends_at
            ])->count();
    }

    private function getBadgesCount(User $user): int
    {
        return $user->badges()->count();
    }

    public function getMeetingsCount(User $user): int
    {
        return $user->meetings()->count();
    }
}
