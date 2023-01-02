<?php

namespace App\Console\Commands;

use App\Models\User\User;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class RecordRankingCommand extends Command
{

    protected $signature = 'system:ranking-cache';

    protected $description = 'dasdas';

    public function handle()
    {
        User::query()
            ->lockForUpdate()
            ->select('id')
            ->whereDoesntHave('seasonInfo')
            ->orderByDesc('level')
            ->orderByDesc('current_exp')
            ->orderBy('id')
            ->chunk(500, fn (Collection $users) => $users->each(fn ($user) => $user->ranking_position));
    }
}
