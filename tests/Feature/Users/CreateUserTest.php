<?php

namespace Feature\Users;

use Laravel\Lumen\Testing\DatabaseMigrations;
use Symfony\Component\HttpFoundation\Response;
use TestCase;

class CreateUserTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function test_bot_can_create_a_new_user()
    {
        $payload = ['discord_id' => '13374002922'];
        $response = $this->post(route('users.store'), $payload, $this->getHeaders());
        $response->assertResponseStatus(Response::HTTP_CREATED);
        $this->seeInDatabase('users', $payload);
    }

    public function test_bot_without_a_key_should_not_create_a_user()
    {
        $payload = ['discord_id' => '13374002922'];
        $response = $this->post(route('users.store'), $payload);
        $response->seeStatusCode(Response::HTTP_UNAUTHORIZED);
    }
}
