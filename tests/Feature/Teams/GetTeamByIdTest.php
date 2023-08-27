<?php

namespace Tests\Feature\Teams;

use Heart\Team\Infrastructure\Models\Team;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class GetTeamByIdTest extends TestCase
{
    use DatabaseTransactions;

    private Team $team;

    protected function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $this->team = Team::factory()->create(['slug' => 'he4rtless']);
    }


    /** @dataProvider retrieveSuccessfulTeamDataProvider */
    public function test_user_can_retrieve_a_specific_team(mixed $payload): void
    {
        $this->getJson(route('teams.show', ['team' => $this->team->slug]))
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'members_count'
            ]);
    }

    /** @dataProvider retrieveInvalidTeamDataProvider */
    public function test_user_should_receive_404_to_a_invalid_team($payload)
    {
        $this->getJson(route('teams.show', ['team' => 9]))
            ->assertNotFound();
    }

    public static function retrieveSuccessfulTeamDataProvider(): array
    {
        return [
            'retrieve with id' => [
                'payload' => '1'
            ],
            'retrieve with slug' => [
                'payload' => 'he4rtless',
            ],
        ];
    }

    public static function retrieveInvalidTeamDataProvider(): array
    {
        return [
            'retrieve with id' => [
                'payload' => '123'
            ],
            'retrieve with slug' => [
                'payload' => 'he4rtless-ftw',
            ],
        ];
    }
}
