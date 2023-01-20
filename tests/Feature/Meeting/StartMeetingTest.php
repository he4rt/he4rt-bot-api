<?php

namespace Tests\Feature\Meeting;

use Heart\Meeting\Infrastructure\Models\MeetingType;
use Heart\Provider\Infrastructure\Models\Provider;
use Heart\User\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class StartMeetingTest extends TestCase
{
    use DatabaseMigrations;

    public function testBotCanStartNewMeeting(): void
    {
        // TODO: migrar tudo que for DiscordId para ProviderID
        // TODO: arrumar possivel bagunça do banco
        // TODO: repassar todo o fluxo novo


        // Arrange
        $providerName = 'discord';
        $provider = Provider::factory()->create(['provider' => $providerName]);

        $meetingType = MeetingType::factory()->create();
        $payload = [
            'meeting_type_id' => $meetingType->getKey(),
            'provider_id' => $provider->provider_id
        ];
        $expectedResponse = [
            'meeting_type_id' => $meetingType->getKey(),
            'admin_id' => $provider->user_id,
        ];

        // Act
        $response = $this
            ->actingAsAdmin()
            ->postJson(route('events.meeting.postMeeting', ['provider' => $providerName]), $payload);


        // Assert
        $response->assertStatus(Response::HTTP_CREATED)
            ->assertSee($expectedResponse);

        $this->assertDatabaseHas('meetings', $expectedResponse);
        $this->assertTrue(Cache::has('current-meeting'));
    }
}
