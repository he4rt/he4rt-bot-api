<?php

namespace Tests\Feature\Messages;

use App\Models\Gamefication\Season;
use App\Models\User\User;
use Carbon\Carbon;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Symfony\Component\HttpFoundation\Response;
use Tests\Providers\User\MessageProvider;
use Tests\TestCase;

class CreateMessageTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');

        $season = Season::factory()->activeSeason()->create();
        config(['he4rt.season.id' => $season->getKey()]);
    }

    public function test_bot_can_create_a_user_message()
    {
        $user = User::factory()->create([
            'level' => 3,
            'current_exp' => 1
        ]);

        $payload = MessageProvider::validMessage();

        $response = $this->post(
            route('users.messages.store', ['discordId' => $user->discord_id]),
            $payload,
            $this->getHeaders()
        );


        $response->seeStatusCode(Response::HTTP_NO_CONTENT);
        $this->seeInDatabase('user_messages', [
            'user_id' => $user->getKey(),
            'message_content' => $payload['message_content']
        ]);
    }

    public function test_bot_without_a_key_should_save_a_user_message()
    {
        $response = $this->post(route('users.messages.store', ['discordId' => 123]));
        $response->seeStatusCode(Response::HTTP_UNAUTHORIZED);
    }
}
