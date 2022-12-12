<?php

namespace Feature\Events\Badges;

use App\Models\Events\Badge;
use App\Models\User\User;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ClaimBadgeTest extends TestCase
{
    use DatabaseMigrations;

    public function test_bot_can_give_users_a_badge(): void
    {
        $user = User::factory()->create();
        $badge = Badge::factory()->create(['active' => true]);

        $response = $this->post(
            route('users.badges.claim', ['discordId' => $user->discord_id]),
            ['redeem_code' => $badge->redeem_code], $this->getHeaders()
        );

        $response->seeStatusCode(Response::HTTP_CREATED)
            ->seeJson(['message' => __('badges.success', ['badgeName' => $badge->name])]);

        $this->seeInDatabase('user_badges', [
            'user_id' => $user->getKey(),
            'badge_id' => $badge->getKey()
        ]);
    }

    public function test_bot_should_not_give_the_same_badge_twitce(): void
    {
        $badge = Badge::factory()->create();
        $user = User::factory()->create();
        $user->badges()->attach($badge);

        $response = $this->post(route('users.badges.claim', ['discordId' => $user->discord_id]), ['redeem_code' => $badge->redeem_code], $this->getHeaders());

        $response->seeStatusCode(Response::HTTP_BAD_REQUEST)
            ->seeJson(['message' => __('badges.errors.alreadyClaimed')]);
    }

    public function test_bot_should_not_give_an_inactive_badge(): void
    {
        $badge = Badge::factory()->create(['active' => false]);
        $user = User::factory()->create();

        $response = $this->post(route('users.badges.claim', ['discordId' => $user->discord_id]), ['redeem_code' => $badge->redeem_code], $this->getHeaders());

        $response->seeStatusCode(Response::HTTP_BAD_REQUEST)
            ->seeJson(['message' => __('badges.errors.inactive')]);
    }
}
