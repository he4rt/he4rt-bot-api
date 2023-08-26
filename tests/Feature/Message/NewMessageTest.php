<?php

namespace Tests\Feature\Message;

use Heart\Character\Infrastructure\Models\Character;
use Heart\Meeting\Infrastructure\Models\Meeting;
use Heart\Provider\Infrastructure\Models\Provider;
use Heart\User\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class NewMessageTest extends TestCase
{
    use DatabaseTransactions;

    public function testCanCreateAMessage(): void
    {
        Cache::tags(['meetings'])->flush();

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
            'sent_at' => now()->toDateTimeString(),
        ];

        $this
            ->actingAsAdmin()
            ->postJson(route('messages.create', ['provider' => $provider->provider]), $payload)
            ->assertNoContent();

        $this->assertDatabaseMissing('characters', [
            'user_id' => $user->getKey(),
            'experience' => 1,
        ]);
    }

    public function testCanCreateAMessageWithLevelZero(): void
    {
        Cache::tags(['meetings'])->flush();

        $user = User::factory()
            ->has(Character::factory(['experience' => 0]), 'character')
            ->has(Provider::factory(), 'providers')
            ->create();
        $provider = $user->providers[0];
        $payload = [
            'provider' => $provider->provider,
            'provider_id' => $provider->provider_id,
            'provider_message_id' => '12312312',
            'channel_id' => '312321',
            'content' => '321312',
            'sent_at' => now()->toDateTimeString(),
        ];

        $this
            ->actingAsAdmin()
            ->postJson(route('messages.create', ['provider' => $provider->provider]), $payload)
            ->assertNoContent();

        $this->assertDatabaseMissing('characters', [
            'user_id' => $user->getKey(),
            'experience' => 1,
        ]);
    }

    public function testCanCreateAMessageAndReceiveAMeetingCheck(): void
    {
        Cache::tags(['meetings'])->flush();

        $user = User::factory()
            ->has(Character::factory(['experience' => 1]), 'character')
            ->has(Provider::factory(), 'providers')
            ->create();

        $meeting = Meeting::factory()
            ->unfinished()
            ->create();

        Cache::tags(['meetings'])->put('current-meeting', $meeting->id);

        $provider = $user->providers[0];
        $payload = [
            'provider' => $provider->provider,
            'provider_id' => $provider->provider_id,
            'provider_message_id' => '12312312',
            'channel_id' => '312321',
            'content' => '321312',
            'sent_at' => now()->toDateTimeString(),
        ];

        $this
            ->actingAsAdmin()
            ->postJson(route('messages.create', ['provider' => $provider->provider]), $payload)
            ->assertNoContent();

        $this->assertDatabaseMissing('characters', [
            'user_id' => $user->getKey(),
            'experience' => 1,
        ]);

        $this->assertDatabaseHas('meeting_participants', [
            'meeting_id' => $meeting->id,
            'user_id' => $user->id,
        ]);
        $userAttendedCacheKey = sprintf('meeting-%s-attended', $user->id);
        $this->assertTrue(Cache::tags(['meetings'])->has($userAttendedCacheKey));
        Cache::tags(['meetings'])->flush();

        $this->assertFalse(Cache::tags(['meetings'])->has($userAttendedCacheKey));
    }
}
