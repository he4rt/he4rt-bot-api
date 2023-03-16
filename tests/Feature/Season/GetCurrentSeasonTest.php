<?php

namespace Tests\Feature\Season;

use Heart\Season\Infrastructure\Models\Season;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class GetCurrentSeasonTest extends TestCase
{
    use DatabaseTransactions;

    public function testGetCurrentSeasonSuccess(): void
    {
        $season = Season::factory()->create();

        Config::set('he4rt.season.id', $season->id);

        $response = $this->actingAsAdmin()->get(route('seasons.current'));

        $response->assertOk();
        $response->assertJsonStructure([
            'id',
            'name',
            'description',
            'messagesCount',
            'participantsCount',
            'meetingCount',
            'badgesCount',
            'startAt',
            'endAt',
        ]);
    }
}
