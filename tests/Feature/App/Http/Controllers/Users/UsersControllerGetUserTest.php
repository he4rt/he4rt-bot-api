<?php

namespace Feature\App\Http\Controllers\Users;

use App\Models\User\User;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Symfony\Component\HttpFoundation\Response;
use TestCase;

class UsersControllerGetUserTest extends TestCase
{
    use DatabaseMigrations;

    public function test_find_user_by_id()
    {
        // Prepare
        $this->artisan('db:seed');
        $user = User::factory()->create();

        // Act
        $response = $this->get(route('user-get', ['discordId' => $user->discord_id]), [
            'Authorization' => config('he4rt.server_key')
        ]);

        // Assert
        $response->assertResponseOk();
        $response->seeJsonContains(['name' => $user->name]);
    }

    public function test_unauthorized_request()
    {
        // Prepare
        $this->artisan('db:seed');
        $user = User::factory()->create();

        // Act
        $response = $this->get(route('user-get', ['discordId' => $user->discord_id]));

        // Assert
        $response->assertResponseStatus(Response::HTTP_UNAUTHORIZED);
    }

}
