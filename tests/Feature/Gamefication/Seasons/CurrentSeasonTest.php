<?php

namespace Tests\Feature\Gamefication\Seasons;

use App\Models\Gamefication\Season;
use Symfony\Component\HttpFoundation\Response;
use TestCase;

class CurrentSeasonTest extends TestCase
{
    public function testBotCanGetCurrentSeason(): void
    {
        // Prepare
        $season = Season::factory()->activeSeason()->create();

        // Act
        $response = $this->get(route('seasons.current'), $this->getHeaders());

        // Assert
        $response->seeStatusCode(Response::HTTP_OK)
            ->seeJsonStructure(array_keys($season->toArray()));
    }
}
