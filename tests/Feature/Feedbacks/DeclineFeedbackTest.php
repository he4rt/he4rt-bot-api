<?php

declare(strict_types=1);

namespace Tests\Feature\Feedbacks;

use App\Models\Feedback\Feedback;
use App\Models\User\User;
use Carbon\Carbon;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class DeclineFeedbackTest extends TestCase
{
    use DatabaseMigrations;

    private Feedback $feedback;

    protected function setUp(): void
    {
        parent::setUp();

        $this->feedback = Feedback::factory()->create();
    }

    /** @test */
    public function returnsErrorUsingInvalidUser()
    {
        $response = $this->post($this->route(), ['staff_id' => 0]);

        $response->assertResponseStatus(Response::HTTP_NOT_FOUND);
    }

    /** @test */
    public function canDeclineFeedbacks()
    {
        Carbon::setTestNow();

        /** @var User $staff */
        $staff = User::factory()->create();

        $response = $this->post($this->route(), ['staff_id' => $staff->discord_id]);

        $response->assertResponseStatus(Response::HTTP_OK);

        $this->assertEquals(
            $this->feedback->reviews()->first()->declined_at,
            Carbon::now()
        );
    }

    private function route(?Feedback $feedback = null): string
    {
        $feedback = $feedback ?: $this->feedback;

        return route('feedback.review.decline', ['feedbackId' => $feedback->getKey()]);
    }
}
