<?php

namespace Tests\Feature\Meeting;

use Heart\Meeting\Infrastructure\Models\MeetingType;
use Heart\User\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class CreateMeetingTest extends TestCase
{
    use DatabaseMigrations;

    public function testBotCanStartNewMeeting(): void
    {
        // Arrange
        $user = User::factory()->create();
        $meetingType = MeetingType::factory()->create();
        $payload = [
            'meeting_type_id' => $meetingType->getKey(),
            'discord_id' => $user->discord_id
        ];
        $expectedResponse = [
            'admin_id' => $user->getKey(),
            'meeting_type_id' => $meetingType->getKey()
        ];

        // Act
        $response = $this->actingAsAdmin()->post(route('events.meeting.postMeeting'), $payload);

        // Assert
        $response->seeStatusCode(Response::HTTP_CREATED)->seeJson($expectedResponse);
        $this->seeInDatabase('meetings', $expectedResponse);
    }
}
