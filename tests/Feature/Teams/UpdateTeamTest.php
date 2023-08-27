<?php

namespace Feature\Teams;

use Heart\Team\Infrastructure\Models\Team;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UpdateTeamTest extends TestCase
{
    use DatabaseTransactions;

    private readonly Team $team;

    protected function setUp(): void
    {
        parent::setUp();
        $this->team = Team::factory()->create();
    }

    public function test_user_can_update_a_team_with_slug(): void
    {
        $this->putJson(route('teams.update', ['team' => $this->team->slug]), [
            'name' => 'é o canhas',
            'slug' => 'canhas-slug',
            'description' => 'se não fosse open source, eu poderia fazer isso?',
        ])->assertOk();

        $this->assertDatabaseHas('teams', [
            'name' => 'é o canhas',
            'slug' => 'canhas-slug',
            'description' => 'se não fosse open source, eu poderia fazer isso?',
        ]);
    }

    public function test_user_can_update_a_team_with_id(): void
    {
        $this->putJson(route('teams.update', ['team' => $this->team->getKey()]), [
            'name' => 'é o canhas',
            'slug' => 'canhas-slug',
            'description' => 'se não fosse open source, eu poderia fazer isso?',
        ])->assertOk();

        $this->assertDatabaseHas('teams', [
            'name' => 'é o canhas',
            'slug' => 'canhas-slug',
            'description' => 'se não fosse open source, eu poderia fazer isso?',
        ]);
    }
}
