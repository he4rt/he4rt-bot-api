<?php

namespace Tests\Feature\Character;

use Heart\Character\Infrastructure\Models\Character;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ClaimDailyBonusTest extends TestCase
{
    public function testSuccess()
    {
        $character = Character::factory()->create([
            'daily_bonus_claimed_at' => now()
        ]);
        $this->travelTo(now()->addHours(24)->addMinutes(2));

        $this->postJson(route('characters.dailyReward', ['characterId' => $character->getKey()]))
            ->assertNoContent();

        $this->assertDatabaseMissing('characters', [
            'daily_bonus_claimed_at' => $character->daily_bonus_claimed_at,
        ]);
    }

    public function testShouldNotClaimBefore24Hours(): void
    {
        $character = Character::factory()->create([
            'daily_bonus_claimed_at' => now()
        ]);

        $this->postJson(route('characters.dailyReward', ['characterId' => $character->getKey()]))
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }
}
