<?php

namespace Feature\Events\Meetings;

use App\Models\Events\MeetingType;
use App\Models\User\User;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Symfony\Component\HttpFoundation\Response;
use TestCase;

class StartMeetingTest extends TestCase
{
    use DatabaseMigrations;

    public function testBotCanStartNewMeeting(): void
    {
        // Arrange
        $user = User::factory()->create();
        $meetingType = MeetingType::factory()->create();
        $payload = [
            'meeting_type_id' => $meetingType->id,
            'discord_id' => $user->discord_id
        ];
        $expectedResponse = [
            'user_created_id' => $user->getKey(),
            'meeting_type_id' => $meetingType->id
        ];

        // Act
        $response = $this->post(route('events.meeting.startMeeting'), $payload, $this->getHeaders());

        // Assert
        $response->seeStatusCode(Response::HTTP_CREATED)->seeJson($expectedResponse);
        $this->seeInDatabase('meetings', $expectedResponse);
    }
}
