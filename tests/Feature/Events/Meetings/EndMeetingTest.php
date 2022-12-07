<?php

namespace Feature\Events\Meetings;

use App\Models\Events\Meeting;
use Laravel\Lumen\Testing\DatabaseMigrations;
use TestCase;

class EndMeetingTest extends TestCase
{
    use DatabaseMigrations;

    public function testBotCanEndMeeting(): void
    {
        // Arrange
        $meeting = Meeting::factory()->unfinished()->create();

        // Act
        $response = $this->put(route('events.meeting.endMeeting', ['meetingId' => $meeting->getKey()]), [], $this->getHeaders());

        // Assert
        $meeting->refresh();
        $response->assertResponseOk();
        $this->assertNotNull($meeting->ends_at);
    }
}
