<?php

namespace Tests\Feature\Feedback;

use Heart\Feedback\Infrastructure\Models\Feedback;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class GetFeedbackByIdTest extends TestCase
{
    use DatabaseTransactions;

    public function testCanFindById(): void
    {
        $feedback = Feedback::factory()->create();

        $this
            ->actingAsAdmin()
            ->getJson(route('feedbacks.show', ['feedbackId' => $feedback->id]))
            ->assertStatus(Response::HTTP_OK);
    }
}
