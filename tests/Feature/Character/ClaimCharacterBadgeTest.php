<?php

namespace Tests\Feature\Character;

use Heart\Badges\Infrastructure\Model\Badge;
use Heart\Character\Infrastructure\Models\Character;
use Heart\Provider\Infrastructure\Models\Provider;
use Heart\User\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ClaimCharacterBadgeTest extends TestCase
{
    use DatabaseMigrations;
    public function testCanClaimBadge()
    {
        $badge = Badge::factory()
            ->create();

        $user = User::factory()
            ->has(Character::factory(), 'character')
            ->has(Provider::factory(), 'providers')
            ->create();

        $provider = $user->providers[0];

        $response = $this->postJson(route('characters.claimBadge', [
            'provider' => $provider->provider,
            'providerId' => $provider->provider_id
        ]), ['redeem_code' => $badge->redeem_code]);

        $response->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertDatabaseHas('characters_badges', [
            'character_id' => $user->character->id,
            'badge_id' => $badge->id,
            'claimed_at' => now()
        ]);
    }
}
