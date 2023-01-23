<?php

namespace Tests\Feature\Badge;

use Heart\Badges\Infrastructure\Model\Badge;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class DeleteBadgeTest extends TestCase
{
    public function testCanDeleteBadge()
    {
        $badge = Badge::factory()->create();

        $this->actingAsAdmin()
            ->deleteJson(route('badges.destroy', ['badgeId' => $badge->id]))
            ->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertDatabaseMissing('badges', [
            'id' => $badge->id
        ]);
    }
}
