<?php

namespace Feature\Events\Badges;

use Laravel\Lumen\Testing\DatabaseMigrations;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class CreateBadgeTest extends TestCase
{
    use DatabaseMigrations;

    public function testBotCanCreateNewBadge(): void
    {
        $payload = [
            'name' => 'Badge do Gui',
            'description' => 'Only for the ones that watched his talk at He4rt Meetup December',
            'image_url' => 'https://github.com/gitlherme.png',
            'redeem_code' => 'o_mais_gato_do_planeta',
            'active' => false
        ];

        $response = $this->post(route('events.badges.store'), $payload, $this->getHeaders());

        $response->seeStatusCode(Response::HTTP_CREATED)
            ->seeJson($payload);

        $this->seeInDatabase('badges', $payload);
    }
}
