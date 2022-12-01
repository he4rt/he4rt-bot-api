<?php

namespace Tests\Feature\Messages;

use App\Models\User\User;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Symfony\Component\HttpFoundation\Response;
use TestCase;

class CreateMessageTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $this->artisan('db:seed');

        config(['he4rt.season' => 1]);
    }

    public function test_bot_can_create_a_user_message()
    {
        $user = User::factory()->create([
            'level' => 3,
            'current_exp' => 1
        ]);

        $payload = [
            'message' => 'me dá sub ai namoral',
        ];

        $response = $this->post(
            route('users.messages.store', ['discordId' => $user->discord_id]),
            $payload,
            $this->getHeaders()
        );

        $response->seeStatusCode(Response::HTTP_NO_CONTENT);
        $this->seeInDatabase('user_messages', [
            'user_id' => $user->getKey(),
            'message' => $payload['message']
        ]);
    }

    public function test_bot_without_a_key_should_save_a_user_message()
    {
        $response = $this->post(route('users.messages.store', ['discordId' => 123]));
        $response->seeStatusCode(Response::HTTP_UNAUTHORIZED);
    }
}
