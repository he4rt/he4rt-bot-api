<?php

namespace Feature\Teams;

use Heart\Team\Infrastructure\Models\Team;
use Tests\TestCase;

class DeleteTeamTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->team = Team::factory()->create(['slug' => 'he4rtless']);
    }

    public function test_user_can_delete_a_team_with_slug(): void
    {
        $this->deleteJson(route('teams.destroy', ['team' => $this->team->slug]))
            ->assertNoContent();

        $this->assertDatabaseEmpty('teams');
    }

    public function test_user_can_delete_a_team_with_id(): void
    {
        $this->deleteJson(route('teams.destroy', ['team' => $this->team->id]))
            ->assertNoContent();

        $this->assertDatabaseEmpty('teams');
    }
}
