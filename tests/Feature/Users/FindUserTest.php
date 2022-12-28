<?php

namespace Tests\Feature\Users;

use App\Models\User\User;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class FindUserTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function test_bot_can_find_a_user_by_id(): void
    {
        $user = User::factory()
            ->create();

        $response = $this->get(route('users.show', ['discordId' => $user->discord_id]), $this->getHeaders());
        dd($response->response->json());
        $response->seeStatusCode(Response::HTTP_OK)
            ->seeJsonStructure(array_keys($user->toArray()));
    }

    public function test_bot_without_a_key_should_not_look_for_a_user()
    {
        $response = $this->get(route('users.show', ['discordId' => 13374002922]));
        $response->assertResponseStatus(Response::HTTP_UNAUTHORIZED);
    }
}
