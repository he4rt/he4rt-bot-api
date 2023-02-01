<?php

namespace Tests\Feature\Season;

use Heart\Season\Infrastructure\Models\Season;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class GetSeasonsTest extends TestCase
{
    use DatabaseTransactions;

    public function testGetSeasonsSuccess(): void
    {
        Season::factory()->create();

        $response = $this->actingAsAdmin()->get(route('get-seasons'));

        $response->assertOk();
        $response->assertJsonStructure([
            [
                'id',
                'name',
                'description',
                'messagesCount',
                'participantsCount',
                'meetingCount',
                'badgesCount',
                'startAt',
                'endAt',
            ]
        ]);
    }
}
