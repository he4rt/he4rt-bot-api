<?php

namespace Tests\Feature\Commands;

use App\Actions\Gamefication\Season\FinishSeason;
use App\Models\Events\Badge;
use App\Models\Events\Meeting;
use App\Models\Gamefication\ExperienceTable;
use App\Models\Gamefication\Season;
use App\Models\User\Message;
use App\Models\User\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class FinishSeasonTest extends TestCase
{
    use DatabaseMigrations;

    public function test_wipe_with_valid_user()
    {

        $season = Season::factory()->activeSeason()->create();
        Config::set('he4rt.season.id', $season->getKey());

        ExperienceTable::factory()
            ->count(5)
            ->create(['required' => 400]);

        User::factory()
            ->create(['level' => 4]);

        $user = User::factory()
            ->has(Message::factory()->count(3))
            ->create(['level' => 3]);

        $badge = Badge::factory()
            ->create();

        $meeting = Meeting::factory()
            ->create(['admin_id' => $user->getKey()]);

        $user->meetings()->attach($meeting, ['attend_at' => Carbon::now()]);
        $user->badges()->attach($badge);

        $action = new FinishSeason();
        $currentExp = $user->current_exp;
        $action->handle();

        $this->seeInDatabase('user_seasons', [
            'user_id' => $user->getKey(),
            'season_id' => $season->getKey(),
            'level' => 3,
            'messages_count' => 3,
            'badges_count' => 1,
            'meetings_count' => 1,
            'experience' => $currentExp,
            'ranking_position' => 2,
        ]);
    }


}
