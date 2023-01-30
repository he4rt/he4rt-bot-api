<?php

namespace Heart\Feedback\Domain\Actions;

use Heart\Feedback\Domain\DTOs\FeedbackReviewDTO;
use Heart\Feedback\Domain\Repositories\FeedbackRepository;

class PersistFeedbackReview
{
    public function __construct(private readonly FeedbackRepository $feedbackRepository)
    {
    }

    public function handle(FeedbackReviewDTO $feedbackReviewDTO)
    {
        return $this->feedbackRepository->reviewFeedback($feedbackReviewDTO);
    }
}
