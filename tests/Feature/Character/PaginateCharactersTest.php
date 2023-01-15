<?php

namespace Tests\Feature\Character;

use Heart\Character\Infrastructure\Models\Character;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class PaginateCharactersTest extends TestCase
{
    use DatabaseMigrations;

    public function testSuccess()
    {
        $character = Character::factory()->create([
            'daily_bonus_claimed_at' => now()
        ]);

        $this->getJson(route('characters.getCharacters', ['characterId' => $character->getKey()]))
            ->assertStatus(Response::HTTP_OK)
            ->assertSee($character->experience);
    }
}
