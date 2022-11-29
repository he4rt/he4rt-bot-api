<?php

namespace Feature\Events\Badges;

use App\Models\Events\Badge;
use App\Models\User\User;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Symfony\Component\HttpFoundation\Response;
use TestCase;

class ClaimBadgeTest extends TestCase
{
    use DatabaseMigrations;

    public function test_bot_can_give_users_a_badge()
    {
        $user = User::factory()->create();
        $badge = Badge::factory()->create();

        $response = $this->post(route('users.badges.claim', ['discordId' => $user->discord_id]), ['redeem_code' => $badge->redeem_code], $this->getHeaders());

        $response->seeStatusCode(Response::HTTP_CREATED)
            ->seeJson(['message' => sprintf('You got the %s badge. Congratz!', $badge->name)]);

        $this->seeInDatabase('user_badges', [
            'user_id' => $user->getKey(),
            'badge_id' => $badge->getKey()
        ]);
    }
}
