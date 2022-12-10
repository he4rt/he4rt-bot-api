<?php

namespace Feature\Events\Meetings;

use App\Models\Events\Meeting;
use App\Models\User\User;
use Illuminate\Support\Carbon;
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
            ->seeStatusCode(Response::HTTP_CREATED)
            ->seeJson(['message' => __('meetings.success.attendMeeting')]);
        $this->seeInDatabase('meeting_participants', $expectedResponse);
    }

    public function testUserCantReAttendAOngoinMeeting(): void
    {
        // Arrange
        $user = User::factory()->create();
        $meeting = Meeting::factory()->unfinished()->create();
        $meeting->users()->attach($user->getKey(), ['attend_at' => Carbon::now()]);

        $payload = [
            'discord_id' => $user->discord_id
        ];

        // Act
        $response = $this->post(route('events.meeting.postAttendMeeting'), $payload, $this->getHeaders());

        // Assert
        $response
            ->seeStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->seeJson(['message' => __('meetings.errors.alreadyAttended')]);
    }

    public function testUserCantAttendAEndedMeeting(): void
    {
        // Arrange
        $user = User::factory()->create();
        $meeting = Meeting::factory()->create();
        $payload = [
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
            ->seeJson(['message' => __('meetings.errors.noActiveMeetings')]);
        $this->notSeeInDatabase('meeting_participants', $expectedResponse);
    }
}
