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
    /** @dataProvider socialsDataProvider */
    public function testUserShouldNotSendWrongSocialLinks(array $payload, int $expectedResponse)
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->put(route('users.update', ['discordId' => $user->discord_id]),
            $payload,
            $this->getHeaders()
        );

        $response->seeStatusCode($expectedResponse);
    }

    public static function socialsDataProvider(): array
    {
        return [
            'github #1' => [
                'payload' => [
                    'git' => 'https://github.com',
                ],
                'expectedStatus' => Response::HTTP_OK,
            ],
            'github #2' => [
                'payload' => [
                    'git' => 'github.com',
                ],
                'expectedStatus' => Response::HTTP_OK
            ],
            'linkedin #1' => [
                'payload' => [
                    'linkedin' => 'linkedin.com',
                ],
                'expectedStatus' => Response::HTTP_OK
            ],
            'linkedin #2' => [
                'payload' => [
                    'linkedin' => 'https://linkedin.com',
                ],
                'expectedStatus' => Response::HTTP_OK
            ],
        ];
    }
}
