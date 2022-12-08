<?php

namespace App\Actions\Feedback;

use App\Models\Feedback\Feedback;
use App\Repositories\Feedback\FeedbackRepository;
use App\Repositories\Feedback\FeedbackReviewRepository;

class CreateFeedback
{
    private FeedbackRepository $feedbackRepository;

    public function __construct(FeedbackRepository $feedbackRepository)
    {
        $this->feedbackRepository = $feedbackRepository;
    }

    public function handle(array $payload): Feedback
    {
        return $this->feedbackRepository->create($payload);
    }
}
