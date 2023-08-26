<?php

namespace Heart\Team\Infrastructure\Factories;

use Heart\Team\Infrastructure\Models\Member;
use Heart\Team\Infrastructure\Models\Role;
use Heart\Team\Infrastructure\Models\Team;
use Heart\User\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MemberFactory extends Factory
{
    protected $model = Member::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'member_id' => User::factory(),
            'role_id' => Role::factory(),
        ];
    }
}
