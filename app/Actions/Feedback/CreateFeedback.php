<?php

namespace App\Actions\Feedback;

use App\Models\Feedback\Feedback;
use App\Repositories\Feedback\FeedbackRepository;

class CreateFeedback
{
    private FeedbackRepository $repository;

    public function __construct(FeedbackRepository $repository)
    {
        $this->repository = $repository;
    }

    public function handle(array $payload): Feedback
    {
        return $this->repository->create($payload);
    }
}
