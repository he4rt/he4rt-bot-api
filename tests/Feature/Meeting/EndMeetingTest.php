<?php

namespace Meeting;

use Heart\Meeting\Infrastructure\Models\Meeting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class EndMeetingTest extends TestCase
{
    use DatabaseTransactions;

    public function testEndMeeting(): void
    {
        $meeting = Meeting::factory()->create();
        Cache::tags(['meetings'])->set('current-meeting', $meeting->id);

        $this->actingAsAdmin()
            ->postJson(route('events.meeting.postEndMeeting', ['provider' => 'discord']))
            ->assertNoContent();

        $this->assertDatabaseMissing('meetings', [
            'id' => $meeting->id,
            'ends_at' => 'null',
        ]);
    }
}
