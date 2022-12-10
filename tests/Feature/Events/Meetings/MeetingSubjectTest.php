<?php

namespace Feature\Events\Meetings;

use App\Models\Events\Meeting;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Symfony\Component\HttpFoundation\Response;
use TestCase;

class MeetingSubjectTest extends TestCase
{
    use DatabaseMigrations;

    public function testBotCanAppendMeetingSubject(): void
    {
        // Arrange
        $meeting = Meeting::factory()->unfinished()->create();
        $payload = [
            'content' => 'Meeting subject'
        ];
        $expectedResponse = [
            'id' => $meeting->getKey(),
            'content' => 'Meeting subject'
        ];

        // Act
        $response = $this->patch(
            route('events.meeting.postMeetingSubject', ['meetingId' => $meeting->getKey()]),
            $payload,
            $this->getHeaders()
        );

        // Assert
        $response->seeStatusCode(Response::HTTP_OK)->seeJson($expectedResponse);
        $this->seeInDatabase('meetings', $expectedResponse);
    }

    public function testAttemptToAddSubjectToInvalidMeeting(): void
    {
        // Arrange
        $payload = [
            'content' => 'Meeting subject'
        ];

        // Act
        $response = $this->patch(
            route('events.meeting.postMeetingSubject', ['meetingId' => 999]),
            $payload,
            $this->getHeaders()
        );

        // Assert
        $response->seeStatusCode(Response::HTTP_NOT_FOUND);
    }
}
