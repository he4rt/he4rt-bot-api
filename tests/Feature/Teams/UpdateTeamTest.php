<?php

namespace Feature\Teams;

use Heart\Team\Infrastructure\Models\Team;
use Tests\TestCase;

class UpdateTeamTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->team = Team::factory()->create(['slug' => 'he4rtless']);
    }

    public function test_user_can_update_a_team_with_slug(): void
    {
        $this->putJson(route('teams.update', ['team' => $this->team->slug]), [
            'name' => 'é o canhas',
            'slug' => 'droga, its him',
            'description' => 'se não fosse open source, eu poderia fazer isso?',
        ])->assertOk();

        $this->assertDatabaseHas('teams', [
            'name' => 'é o canhas',
            'slug' => 'droga, its him',
            'description' => 'se não fosse open source, eu poderia fazer isso?',
        ]);
    }

    public function test_user_can_update_a_team_with_id(): void
    {
        $this->putJson(route('teams.update', ['team' => $this->team->id]), [
            'name' => 'é o canhas',
            'slug' => 'droga, its him',
            'description' => 'se não fosse open source, eu poderia fazer isso?',
        ])->assertOk();

        $this->assertDatabaseHas('teams', [
            'name' => 'é o canhas',
            'slug' => 'droga, its him',
            'description' => 'se não fosse open source, eu poderia fazer isso?',
        ]);
    }
}
