<?php

namespace Tests\Feature\Commands;

use App\Actions\Commands\MigrateDatabase;
use App\Models\Feedback\Feedback;
use App\Models\Feedback\FeedbackReview;
use App\Models\Gamefication\Season;
use App\Models\User\Level;
use App\Models\User\Message;
use App\Models\User\User;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class MigrateDatabaseTest extends TestCase
{
    use DatabaseMigrations;

    public function testRun()
    {
        $season = Season::factory()->create();
        $user = User::factory()
            ->has(Message::factory(['season_id' => $season->id])->count(3))
            ->has(\App\Models\User\Season::factory(), 'seasonInfo')
            ->create();

        Feedback::factory()
            ->has(FeedbackReview::factory(), 'reviews')
            ->create(['sender_id' => $user->id]);

        $user->levelupLog()->create([
            'season_id' => 1, 'level' => 1
        ]);

        $migrate = new MigrateDatabase();

        $migrate->run();

        $this->assertTrue(true);
    }
}
