<?php

namespace Tests\Feature\Message;

use Heart\Character\Domain\Enums\VoiceStatesEnum;
use Heart\Character\Infrastructure\Models\Character;
use Heart\Provider\Infrastructure\Models\Provider;
use Heart\User\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class NewVoiceMessageTest extends TestCase
{
    use DatabaseTransactions;

    public function testCanCreateVoiceMessage()
    {
        config(['he4rt.season.id' => 2]);

        $user = User::factory()
            ->has(Character::factory(['experience' => 1]), 'character')
            ->has(Provider::factory(), 'providers')
            ->create();

        $provider = $user->providers[0];
        $payload = [
            'provider' => $provider->provider,
            'provider_id' => $provider->provider_id,
            'state' => VoiceStatesEnum::Muted->value,
            'channel_name' => 'Estudando'
        ];

        $this->actingAsAdmin()
            ->post(route('voices.create', $provider->provider), $payload)
            ->assertNoContent();

        $this->assertDatabaseMissing('characters', [
            'user_id' => $user->getKey(),
            'experience' => 1,
        ]);

        $this->assertDatabaseHas('voice_messages', [
            'state' => $payload['state'],
            'channel_name' => $payload['channel_name'],
            'season_id' => 2
        ]);
    }
}
