<?php

namespace Feature\Events\Meetings;

use App\Models\Events\Meeting;
use Laravel\Lumen\Testing\DatabaseMigrations;
use TestCase;

class IndexMeetingTest extends TestCase
{
    use DatabaseMigrations;

    public function testBotCanListAllMeetings(): void
    {
        // Arrange
        $meeting = Meeting::factory()->unfinished()->create();

        // Act
        $response = $this->get(route('events.meeting.getMeetings'), $this->getHeaders());

        // Assert
        $response->assertResponseOk();
        $response->seeJsonStructure(
            [
                'data' => [
                    0 => [
                        'id',
                        'content',
                        'meeting_type_id',
                        'user_created_id',
                        'starts_at',
                        'ends_at',
                        'created_at',
                        'updated_at',
                        'meeting_type' => [
                            'id',
                            'name',
                            'week_day',
                            'start_at',
                            'created_at',
                            'updated_at',
                        ]
                    ]
                ]
            ]
        );
    }
}
