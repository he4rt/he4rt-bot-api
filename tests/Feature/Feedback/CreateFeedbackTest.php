<?php

namespace Tests\Feature\Feedback;

use Heart\Provider\Infrastructure\Models\Provider;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class CreateFeedbackTest extends TestCase
{
    use DatabaseTransactions;

    public function testCanCreate()
    {
        $providerSender = Provider::factory()->create(['provider' => 'discord']);
        $providerTarget = Provider::factory()->create(['provider' => 'discord']);

        $payload = [
            'sender_id' => $providerSender->provider_id,
            'target_id' => $providerTarget->provider_id,
            'message' => 'mt legal vc',
            'type' => 'elogio'
        ];

        $this->postJson(route('feedbacks.create'), $payload)
            ->assertStatus(Response::HTTP_CREATED);

        $payload['sender_id'] = $providerSender->user->id;
        $payload['target_id'] = $providerTarget->user->id;
        $this->assertDatabaseHas('feedbacks', $payload);
    }
}
