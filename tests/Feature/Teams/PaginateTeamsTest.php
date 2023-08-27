<?php

namespace Tests\Feature\Teams;

use Heart\Team\Infrastructure\Models\Team;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PaginateTeamsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_can_list_teams()
    {
        Team::factory()
            ->count(3)
            ->create();

        $this->getJson(route('teams.index'))
            ->assertOk()
            ->assertJsonFragment([
                'total' => 3,
            ]);
    }
}
