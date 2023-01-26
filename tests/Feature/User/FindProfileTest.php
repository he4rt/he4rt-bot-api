<?php

namespace Tests\Feature\User;

use Heart\Badges\Infrastructure\Model\Badge;
use Heart\Character\Domain\Entities\CharacterEntity;
use Heart\Character\Infrastructure\Models\Character;
use Heart\Provider\Infrastructure\Models\Provider;
use Heart\User\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class FindProfileTest extends TestCase
{
    use DatabaseMigrations;

    public function testCanFindProfile()
    {
        $user = User::factory()
            ->has(Character::factory(), 'character')
            ->has(Provider::factory())
            ->create();

        $badge = Badge::factory()->create();

        $character = $user->character;
        $character->badges()->attach($badge->id, ['claimed_at' => now()]);

        $response = $this
            ->actingAsAdmin()
            ->getJson(route('users.profile', ['value' => $user->username]));
        $response->dump('badges');
        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'id',
                'username',
                'character' => [
                    'user_id',
                    'reputation',
                    'level',
                    'experience',
                    'daily_bonus_claimed_at',
                ],
                'badges'
            ]);
    }
}
