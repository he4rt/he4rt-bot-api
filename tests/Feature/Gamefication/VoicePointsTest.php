<?php

declare(strict_types=1);

namespace Tests\Feature\Gamefication;

use App\Exceptions\UserException;
use App\Models\Gamefication\ExperienceTable;
use App\Models\User\User;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;
use TestCase;

class VoicePointsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('gambling.xp.voice_points', 90);
    }

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
        $level = ExperienceTable::factory([
            'required' => 300
        ])->create();

        $user = User::factory([
            'current_exp' => 0,
            'level' => $level->getKey()
        ])->create();

        $response = $this->get(
            route('users.voice.claim', ['discordId' => $user->discord_id]),
            $this->getHeaders()
        );

        $response->assertResponseStatus(Response::HTTP_NO_CONTENT);
        $this->assertEquals($user->current_exp + config('gambling.xp.voice_points'), $user->refresh()->current_exp);
    }
}
