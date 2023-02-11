<?php

namespace Tests\Feature\Ranking;

use Heart\Character\Infrastructure\Models\Character;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class RankingByLevelTest extends TestCase
{
    use DatabaseTransactions;
    public function testCanFetchRanking()
    {
        Character::factory()->count(5)->create();

        $response = $this
            ->actingAsAdmin()
            ->getJson(route('ranking.leveling'));

        $response->assertStatus(Response::HTTP_OK);
    }
}
