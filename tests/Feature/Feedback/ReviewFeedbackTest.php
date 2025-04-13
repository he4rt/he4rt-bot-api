<?php

namespace Tests\Feature\Feedback;

use Heart\Feedback\Infrastructure\Models\Feedback;
use Heart\Provider\Infrastructure\Models\Provider;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ReviewFeedbackTest extends TestCase
{
    use DatabaseTransactions;

    #[DataProvider('dataProvider')]
    public function testCanHandleFeedback(string $action, array $payload, array $expected): void
    {
        $feedback = Feedback::factory()->create();
        $staffProvider = Provider::factory()->create(['provider' => 'discord']);

        $payload['staff_id'] = $staffProvider->provider_id;
        $response = $this
            ->actingAsAdmin()
            ->postJson(route('feedbacks.review', [
                'feedbackId' => $feedback->id,
                'action' => $action,
            ]), $payload);

        $response->assertStatus(Response::HTTP_CREATED);

        $expected['staff_id'] = $staffProvider->user_id;
        $this->assertDatabaseHas('feedback_reviews', $expected);
    }

    public static function dataProvider(): array
    {
        return [
            'approve feedback' => [
                'action' => 'approved',
                'payload' => [],
                'expected' => [
                    'status' => 'approved',
                ],
            ],
            'decline feedback' => [
                'action' => 'declined',
                'payload' => [
                    'reason' => 'bobo',
                ],
                'expected' => [
                    'status' => 'declined',
                    'reason' => 'bobo',
                ],
            ],
        ];
    }
}
