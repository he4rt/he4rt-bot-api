<?php

namespace Feature\Events\Meetings;

use App\Models\Events\Meeting;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class EndMeetingTest extends TestCase
{
    use DatabaseMigrations;

    public function testBotCanEndMeeting(): void
    {
        // Arrange
        $meeting = Meeting::factory()->unfinished()->create();

        // Act
        $response = $this->post(
            route('events.meeting.postEndMeeting', ['meetingId' => $meeting->getKey()]),
            [],
            $this->getHeaders()
        );

        // Assert
        $meeting->refresh();
        $response
            ->seeStatusCode(Response::HTTP_OK)
            ->seeJson(['message' => __('meetings.success.endMeeting')]);
        $this->assertNotNull($meeting->ends_at);
    }
}
