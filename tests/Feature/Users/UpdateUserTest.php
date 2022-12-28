<?php

namespace Tests\Feature\Users;

use App\Models\User\User;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Symfony\Component\HttpFoundation\Response;
use Tests\Providers\User\UpdateProvider;
use Tests\TestCase;

class UpdateUserTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    /** @test */
    public function canUpdateUser()
    {
        $user = User::factory()->create();

        $payload = UpdateProvider::validPayload();

        $response = $this->put(
            route('users.update', ['discordId' => $user->discord_id]),
            $payload,
            $this->getHeaders()
        );

        $response->seeStatusCode(Response::HTTP_OK)
            ->seeJsonStructure(array_keys($user->toArray()))
            ->seeJsonContains($payload);

        $this->seeInDatabase('users', array_merge($payload, ['id' => $user->getKey()]));
    }
}
