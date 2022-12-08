<?php

namespace Tests\Feature\Feedbacks;

use App\Models\User\User;
use Tests\Providers\FeedbackProvider;
use Symfony\Component\HttpFoundation\Response;
use TestCase;

/** @group feedback.create */
class CreateFeedbackTest extends TestCase
{
    public string $route;

    /** @test */
    public function botCanCreateFeedback()
    {
        /**
         * @var User $sender
         * @var User $target
         */
        [$sender, $target] = User::factory()->count(2)->create();

        $response = $this->post($this->route, FeedbackProvider::validPayload($sender, $target));

        $response->assertResponseStatus(Response::HTTP_CREATED);
    }

    /** @test */
    public function hasValidationErrors()
    {
        $response = $this->post($this->route, FeedbackProvider::invalidPayload());

        $response->assertResponseStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->assertEquals(
            array_keys($response->response->json()),
            array_keys(FeedbackProvider::validPayload())
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->route = route('feedback.create');
    }
}
