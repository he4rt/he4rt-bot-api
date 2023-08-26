<?php

namespace Tests\Feature\Teams;

use Heart\Team\Infrastructure\Models\Role;
use Heart\Team\Infrastructure\Models\Team;
use Heart\User\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class CreateTeamTest extends TestCase
{
    use DatabaseMigrations;

    public function test_can_create_a_new_team(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['name' => 'leader']);

        $payload = [
            'leader_id' => $user->getKey(),
            'name' => 'He4rtless Cr3w',
            'description' => 'Just another he4rt crew',
            'logo_url' => 'https://placehold.it/300x300'
        ];

        $this->postJson(route('teams.store'), $payload)
            ->assertCreated()
            ->assertJson([
                'id' => 1,
                ...$payload,
                'members_count' => 1
            ]);

        $this->assertDatabaseHas('teams', $payload);

        $this->assertDatabaseHas('team_members', [
            'member_id' => $user->getKey(),
            'team_id' => 1,
            'role_id' => $role->getKey()
        ]);
    }

    public function test_user_should_not_have_leadership_in_more_than_a_team()
    {
        $team = Team::factory()->create();

        $payload = [
            'leader_id' => $team->leader->getKey(),
            'name' => 'He4rtless Cr3w',
            'description' => 'Just another he4rt crew',
            'logo_url' => 'https://placehold.it/300x300'
        ];

        $this->postJson(route('teams.store'), $payload)
            ->assertUnprocessable();
    }
}
