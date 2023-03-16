<?php

namespace Tests\Feature\User;

use Heart\Character\Infrastructure\Models\Character;
use Heart\Character\Infrastructure\Models\PastSeason;
use Heart\Message\Infrastructure\Models\Message;
use Heart\Provider\Infrastructure\Models\Provider;
use Heart\User\Infrastructure\Models\Address;
use Heart\User\Infrastructure\Models\Information;
use Heart\User\Infrastructure\Models\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class UpdateProfileTest extends TestCase
{
    public function testSuccess()
    {
        $user = User::factory()
            ->has(Character::factory()->has(PastSeason::factory()), 'character')
            ->has(Address::factory(), 'address')
            ->has(Information::factory(), 'information')
            ->has(Provider::factory()->has(Message::factory()->count(2)))
            ->create();

        $payload = [
            'info' => [
                'name' => 'daniel corazon',
                'nickname' => 'danielhe4rt#0001',
                'linkedin_url' => 'https://linkedin.com/in/danielheart',
                'github_url' => 'https://github.com/danielhe4rt',
                'birthdate' => '1999-08-03',
                'about' => 'definitely a developer',
            ]
        ];

        $response = $this
            ->actingAsAdmin()
            ->putJson(route('users.profile.update', ['value' => $user->username]), $payload);

        $response->assertStatus(Response::HTTP_OK);

        $this->assertDatabaseHas('user_information', $payload['info']);
    }

    public function testSuccessWithOneField()
    {
        $user = User::factory()
            ->has(Character::factory()->has(PastSeason::factory()), 'character')
            ->has(Address::factory(), 'address')
            ->has(Information::factory(), 'information')
            ->has(Provider::factory()->has(Message::factory()->count(2)))
            ->create();

        $payload = [
            'info' => [
                'github_url' => 'https://github.com/danielhe4rt',
            ]
        ];
        $userExpected = $user->information
            ->only(['nickname', 'linkedin_url']);

        $response = $this
            ->actingAsAdmin()
            ->putJson(route('users.profile.update', ['value' => $user->username]), $payload);


        $userExpected['github_url'] = $payload['info']['github_url'];

        $response->assertStatus(Response::HTTP_OK);

        $this->assertDatabaseHas('user_information', $userExpected);
    }
}
