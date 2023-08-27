<?php

namespace Heart\Team\Infrastructure\Factories;

use Heart\Team\Infrastructure\Models\Invite;
use Heart\Team\Infrastructure\Models\Team;
use Heart\User\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InviteFactory extends Factory
{
    protected $model = Invite::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'member_id' => User::factory(),
            'invited_by' => User::factory(),
            'accepted_at' => null,
        ];
    }
}
