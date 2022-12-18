<?php

namespace Tests\Feature\Gamefication\Seasons;

use App\Models\Gamefication\Season;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class CurrentSeasonTest extends TestCase
{
    use DatabaseMigrations;

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
