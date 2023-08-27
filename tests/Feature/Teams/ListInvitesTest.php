<?php

namespace Feature\Teams;

use Heart\Team\Infrastructure\Models\Invite;
use Heart\User\Infrastructure\Models\User;
use Tests\TestCase;

class ListInvitesTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->invite = Invite::factory()->create(['member_id' => $this->user->id]);
        $this->invite = Invite::factory()->create(['member_id' => $this->user->id]);
    }

    public function test_user_can_see_your_invites(): void
    {
        $this->getJson(route('teams.invite.list', ['user_id' => $this->user->id]))
            ->assertOk()
            ->assertJsonStructure(
                [
                    0 => [
                        'id',
                        'team_id',
                        'member_id',
                        'invited_by',
                        'accepted_at',
                        'created_at',
                        'updated_at',
                    ],
                    1 => [
                        'id',
                        'team_id',
                        'member_id',
                        'invited_by',
                        'accepted_at',
                        'created_at',
                        'updated_at',
                    ],
                ]
            );
    }
}
