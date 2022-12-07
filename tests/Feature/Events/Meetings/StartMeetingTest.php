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
        $user = User::factory()->create();
        $meetingType = MeetingType::factory()->create();
        $payload = [
            'meeting_type_id' => $meetingType->id,
            'discord_id' => $user->discord_id
        ];

        $response = $this->post(route('events.meeting.startMeeting'), $payload, $this->getHeaders());

        $response->seeStatusCode(Response::HTTP_CREATED)
            ->seeJson($payload);

        $this->seeInDatabase('badges', $payload);
    }
}
