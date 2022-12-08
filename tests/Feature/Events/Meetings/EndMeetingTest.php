<?php

namespace Feature\Events\Meetings;

use App\Models\Events\Meeting;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Symfony\Component\HttpFoundation\Response;
use TestCase;

class EndMeetingTest extends TestCase
{
    use DatabaseMigrations;

    public function testBotCanEndMeeting(): void
    {
        // Arrange
        $meeting = Meeting::factory()->unfinished()->create();

        // Act
        $response = $this->put(
            route('events.meeting.putEndMeeting', ['meetingId' => $meeting->getKey()]),
            [],
            $this->getHeaders()
        );

        // Assert
        $meeting->refresh();
        $response
            ->seeStatusCode(Response::HTTP_OK)
            ->seeJson(['message' => __('meetings.success')]);
        $this->assertNotNull($meeting->ends_at);
    }
}
