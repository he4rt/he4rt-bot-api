<?php

namespace Tests\Feature\Teams;

use Heart\Team\Infrastructure\Models\Team;
use Heart\User\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class InviteMemberTest extends TestCase
{
    use DatabaseTransactions;

    public function test_leader_or_subleader_can_invite_a_member(): void
    {
        $team = Team::factory()->create();
        $member = User::factory()->create();

        $payload = [
            'invited_by' => $team->leader_id,
            'member_id' => $member->getKey()
        ];

        $this->postJson(route('teams.invite.store', ['team' => $team->getKey()]), $payload)
            ->assertCreated();

        $this->assertDatabaseHas('team_invites', [
            ...$payload,
            'team_id' => $team->getKey(),
        ]);
    }
}
