<?php

namespace App\Transformers;

use App\Models\User\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ChecklistTransformer
{
    public function handle(string $discordId): array
    {
        return [
            'presented' => true,
            'isActive' => $this->transformLastActivityOnPast7Days($discordId),
            'lastMessages' => $this->transformLastMessages($discordId)
        ];
    }

    private function transformLastActivityOnPast7Days(string $discordId)
    {
        $query = sprintf(
            '
            SELECT
                u.nickname, u.id
            FROM
                users u
            JOIN user_messages um ON
                um.user_id = u.id
            WHERE
                um.created_at > "%s" AND
                u.discord_id = "%s"
            GROUP BY u.nickname
            ',
            Carbon::now()->subDays(7)->format('Y-m-d'),
            $discordId
        );
        $select = DB::select($query);

        return (bool)count($select);
    }

    private function transformLastMessages(string $discordId)
    {
        $user = User::query()
            ->where('discord_id', '=', $discordId)
            ->first();

        return $user->messages()
            ->select(['id', 'message', 'created_at'])
            ->orderBy('id', 'desc')
            ->limit(40)
            ->get();
    }
}
