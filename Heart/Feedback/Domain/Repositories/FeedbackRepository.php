<?php

namespace Heart\Feedback\Domain\Repositories;

use Heart\Feedback\Domain\DTOs\NewFeedbackDTO;
use Heart\Feedback\Domain\DTOs\FeedbackReviewDTO;
use Heart\Feedback\Domain\Entities\FeedbackEntity;

interface FeedbackRepository
{
    public function create(NewFeedbackDTO $dto): FeedbackEntity;

    public function reviewFeedback(FeedbackReviewDTO $dto);

    public function findById(string $id): FeedbackEntity;
}
