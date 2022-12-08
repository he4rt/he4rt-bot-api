<?php

namespace Feature\Events\Meetings;

use App\Models\Events\Meeting;
use App\Models\User\User;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Symfony\Component\HttpFoundation\Response;
use TestCase;

class AttendMeetingTest extends TestCase
{
    use DatabaseMigrations;

    public function testUserCanAttendAOngoinMeeting(): void
    {
        // Arrange
        $user = User::factory()->create();
        $meeting = Meeting::factory()->unfinished()->create();
        $payload = [
            'meeting_id' => $meeting->getKey(),
            'discord_id' => $user->discord_id
        ];
        $expectedResponse = [
            'user_id' => $user->getKey(),
            'meeting_id' => $meeting->getKey()
        ];

        // Act
        $response = $this->post(route('events.meeting.postAttendMeeting'), $payload, $this->getHeaders());

        // Assert
        $response->seeStatusCode(Response::HTTP_CREATED)->seeJson($expectedResponse);
        $this->seeInDatabase('meeting_participants', $expectedResponse);
    }

    public function testUserCantAttendAEndedMeeting(): void
    {
        // Arrange
        $user = User::factory()->create();
        $meeting = Meeting::factory()->create();
        $payload = [
            'meeting_id' => $meeting->getKey(),
            'discord_id' => $user->discord_id
        ];
        $expectedResponse = [
            'user_id' => $user->getKey(),
            'meeting_id' => $meeting->getKey()
        ];

        // Act
        $response = $this->post(route('events.meeting.postAttendMeeting'), $payload, $this->getHeaders());

        // Assert
        $response
            ->seeStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->seeJson(['message' => __('meetings.errors.noAtiveMeetings')]);
        $this->notSeeInDatabase('meeting_participants', $expectedResponse);
    }
}
