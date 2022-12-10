<?php

declare(strict_types=1);

namespace Tests\Feature\Gamefication;

use App\Exceptions\UserException;
use Symfony\Component\HttpFoundation\Response;
use TestCase;
use App\Models\User\User;

class VoicePointsTest extends TestCase
{
    /** @test */
    public function invalidUserId(): void
    {
        $exception = UserException::discordIdNotFound(0);

        $response = $this->get(route('users.voice.claim', ['discordId' => 0]), $this->getHeaders());

        $response->assertResponseStatus($exception->getCode());
        $response->response->assertSee($exception->getMessage());
    }

    /** @test */
    public function updatesUserPoints(): void
    {
        $user = User::factory()->create();

        $response = $this->get(
            route('users.voice.claim', ['discordId' => $user->discord_id]),
            $this->getHeaders()
        );

        $response->assertResponseStatus(Response::HTTP_NO_CONTENT);
        $this->assertEquals($user->money + config('gambling.voice_points'), $user->refresh()->money);
    }
}
