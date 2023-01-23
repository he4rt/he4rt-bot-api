<?php

namespace Tests\Feature\Badge;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class CreateBadgeTest extends TestCase
{
    public function testCanCreateBadge()
    {
        $payload = [
            'provider' => 'twitch',
            'name' => 'Aula foda',
            'description' => 'aula foda do dia foda',
            'image_url' => 'https://http.cat/200',
            'redeem_code' => '123',
            'active' => true,
        ];
        $this->actingAsAdmin()
            ->postJson(route('badges.store'), $payload)
            ->assertStatus(Response::HTTP_CREATED);

        $this->assertDatabaseHas('badges', $payload);
    }
}
