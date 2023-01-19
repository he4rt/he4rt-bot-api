<?php

namespace Tests\Feature\Message;

use Heart\Character\Infrastructure\Models\Character;
use Heart\Provider\Infrastructure\Models\Provider;
use Heart\User\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class NewMessageTest extends TestCase
{
    use DatabaseMigrations;

    public function testCanCreateAMessage(): void
    {
        $user = User::factory()
            ->has(Character::factory(['experience' => 1]), 'character')
            ->has(Provider::factory(), 'providers')
            ->create();
        $provider = $user->providers[0];
        $payload = [
            'provider' => $provider->provider,
            'provider_id' => $provider->provider_id,
            'provider_message_id' => '12312312',
            'channel_id' => '312321',
            'content' => '321312',
            'sent_at' => now()->toDateTimeString()
        ];

        $this
            ->postJson(route('messages.create', ['provider' => $provider->provider]), $payload)
            ->assertNoContent();

        $this->assertDatabaseMissing('characters', [
            'user_id' => $user->getKey(),
            'experience' => 1
        ]);
    }
}
