<?php

namespace Tests\Feature\Commands;

use App\Models\Gamefication\ExperienceTable;
use App\Models\Gamefication\Season;
use App\Models\User\Message;
use App\Models\User\User;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class FinishSeasonCommandTest extends TestCase
{
    use DatabaseMigrations;

    public function test_wipe_with_valid_user()
    {
        $season = Season::factory()->activeSeason()->create();
        config(['he4rt.season.id' => $season->getKey()]);

        ExperienceTable::factory()
            ->count(5)
            ->create(['required' => 400]);

        $user = User::factory()->state(['level' => 3])
            ->create();

        Message::factory()
            ->count(3)
            ->state(['user_id' => $user->getKey()])
            ->create();


        $this->artisan('season:end');
        $this->seeInDatabase('user_seasons', [
            'user_id' => $user->getKey(),
            'season_id' => $season->getKey(),
            'level' => $user->level,
            'messages_count' => 3
        ]);
    }


}
